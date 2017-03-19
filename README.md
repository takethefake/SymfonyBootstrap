# Docker Symfony (PHP7-FPM - NGINX - MySQL - ELK) App + User OAuth

## Based On

  1. https://github.com/maxpou/docker-symfony for Docker Setup
  2. http://symfony.com/doc/current/security/custom_provider.html for own Userprovider
  3. https://causeyourestuck.io/2016/07/19/oauth2-explained-part-1-principles-terminology/ for OAuth Configuration
  4. https://bitgandtter.blog/2015/09/30/symfony-a-restful-app-security-securing-the-token-path-fixed/ for OAuth in Symfony

![](doc/schema.png)

Docker-symfony gives you everything you need for developing Symfony application. This complete stack run with docker and [docker-compose (1.7 or higher)](https://docs.docker.com/compose/).

## Installation

1. Change the .env file to your needs, but it should work the way it is right now.

2. Build/run containers with (with and without detached mode)

    ```bash
    $ docker-compose build
    $ docker-compose up -d
    ```

3. Update your system host file (add symfony.dev)

    ```bash
    $ sudo echo "127.0.0.1 symfony.dev" >> /etc/hosts
    ```

4. Prepare Symfony app
    1. Update app/config/parameters.yml

    ```bash
    $ mv app/config/parameter.yml.dist app/config/parameter.yml
    ```

    2. Composer install & create database

        ```bash
        $ docker-compose exec php bash
        $ composer install
        $ php bin/console doctrine:schema:update --force
        $ php bin/console doctrine:fixtures:load --no-interaction
        ```

    3. Setup OAuth

        ```bash
        $ php bin/console schulzcodes:oauth-server:user:create
        $ php bin/console schulzcodes:oauth-server:client:create --redirect-uri="http://client.local/" --grant-type="authorization_code" --grant-type="password" --grant-type="refresh_token" --grant-type="token" --grant-type="client_credentials"
        ```


## Usage

Just run `docker-compose up -d`, then:

* Symfony app: visit [symfony.dev](http://symfony.dev)  
* Symfony dev mode: visit [symfony.dev/app_dev.php](http://symfony.dev/app_dev.php)  
* Logs (Kibana): [symfony.dev:81](http://symfony.dev:81)
* Logs (files location): logs/nginx and logs/symfony


## Use OAuth
<sub> Taken from https://causeyourestuck.io/2016/07/19/oauth2-explained-part-3-using-oauth2-bare-hands/</sub>

### Authorization Code

That’s the most commonly used one, recommended to authorize end customers. A good example is the Facebook Login for websites. Here’s how it works.
Request this url in the browser:

```
symfony.dev/oauth/v2/auth?client_id=CLIENT_ID&response_type=code&redirect_uri=CLIENT_HOST
```

note: redirect_uri should be identical to the one provided on client creation, otherwise you will get a corresponding error message.
The page you are requesting will offer you a login, then authorization of the client permissions, once you confirm everything it will redirect you back to the url you provided in redirect_url. In our case, redirect will look like

```
CLIENT_HOST/?code=Yjk2MWU5YjVhODBiN2I0ZDRkYmQ1OGM0NGY4MmUyOGM2NDQ2MmY2ZDg2YjUxYjRiMzAwZTY2MDQxZmUzODg2YQ
```

I’ll refer to this long code parameter as CODE in the future. This code is stored on the Provider side, and once you request for the token, it can uniquely identify the client which made request and the user.
It’s time to request the token

#### Request

```
symfony.dev/oauth/v2/token?client_id=CLIENT_ID&client_secret=CLIENT_SECRET&grant_type=authorization_code&redirect_uri=http%3A%2F%2Fclinet.local%2F&code=CODE
```
µ
Most probably this request will fail. That’s because CODE expires rather quickly. Fear not, just request first URL, repeat the process, prepare the second url in the text editor of your choice, copy in the code rather quickly, and you will get the desired result.
It’s a JSON which contains access_token and looks like this


#### Response

```json
{
  "access_token":"NjlmNDNiZTU4ZDY3ZGFlYTI5MGEzNDcxZWVmZDU4Y2E1NGJmZTJlMjNjNzc2M2E0MmZlZTk2ZjliMWE0MDQyNw",
  "expires_in":3600,
  "token_type":"bearer",
  "scope":null,
  "refresh_token":"ZGU2NzlhOTQ2MmRlY2YyYjAyMjBkYmJmMmJhMDllNTgyNmJkNmQxOWZlNGQ4NzczY2RiMThlNmRhMjBiYjFjNg"
}
```

this suggests that access_token expires in 3600 seconds, and to refresh it you have the refresh token. We will discuss how to handle that later on this chapter.

### Implicit Grant

It’s similar to Authorization Code grant, it’s just a bit simpler. You just need to make only one request, and you will get the access_token as a part of redirect URL, there’s no need for second response. That’s for the situations where you trust the user and the client, but you still want the user to identify himself in the browser.

```
symfony.dev/oauth/v2/auth?client_id=2_1y1zqhh7ws5c8kok8g8w88kkokos0wwswwwowos4o48s48s88w&redirect_uri=http%3A%2F%2Fclinet.local%2F&response_type=token
```

then you will get redirected to

```
CLIENT_HOST/#access_token=YWZhZWQ5NjQxOTI2ODJmZWE4YjJiYmExZTIxZmE5OWUxOWZjZjgwZDFlZWMwMjkyZDQwZWU1NWI4YWIzODllNQ&expires_in=3600&token_type=bearer&refresh_token=YzQ1YjRhODk2YzJiYTZmMzNiNjI5ZjI2MDI3ZmMwMDg3MjkxMDdhYmE5YjBlYzRlZmM2M2Q0NTM3ZjFmZDZiYQ
```

### Password flow

Let’s say you have no luxury of redirecting user to some website, then handle redirect call, all you have is just an application which is able to send HTTP requests. And you still want to somehow authenticate user on the server side, and all you have is username and password.
#### Request:

```
symfony.dev/oauth/v2/token?client_id=CLIENT_ID&client_secret=CLIENT_SECRET&grant_type=password&username=USERNAME&password=PASSWORD
```

#### Response:

```json
{
  "access_token":"MjY1MWRhYTAyZDZlOTEyN2EzNTg4MGMwMTcyYjczY2Y0MWI3NzZjODc1OGM2NDdjODgxZjY3YzEyMDdhZjU0Yg",
  "expires_in":3600,
  "token_type":"bearer",
  "scope":null,
  "refresh_token":"MDNmNzBmNWQ2NzdhYWVmYjE2NjI3ZjAyZTM4Y2Q1NDRiNDY1YjUyZGE1ZDk0ODZjYmU0MDM0NTQxNjhiZmU3ZA"
}
```

### Client Credentials

This one is the most simplistic flow of them all. You just need to provide CLIENT_ID and CLIENT_SECRET.

#### Request

```
symfony.dev/oauth/v2/token?client_id=CLIENT_ID&client_secret=CLIENT_SECRET&grant_type=client_credentials
```

#### Response

```json
{
  "access_token":"YTk0YTVjZDY0YWI2ZmE0NjRiODQ4OWIyNjZkNjZlMTdiZGZlNmI3MDNjZGQwYTZkMDNiMjliNDg3NWYwZWI0MQ",
  "expires_in":3600,
  "token_type":"bearer",
  "scope":"user",
  "refresh_token":"ZDU1MDY1OTc4NGNlNzQ5NWFiYTEzZTE1OGY5MWNjMmViYTBiNmRjOTNlY2ExNzAxNWRmZTM1NjI3ZDkwNDdjNQ"
}
```
### Refresh flow

Before I mentioned that `access_tokens` have a lifetime of one hour, after which they will expire. With every `access_token` you were provided a `refresh_token`. You can exchange refresh token and get a new pair of `access_token` and `refresh_token`

#### Request
```
PROVIDER_HOST/oauth/v2/token?client_id=CLIENT_ID&client_secret=CLIENT_SECRET&grant_type=refresh_token&refresh_token=REFRESH_TOKEN
```

#### Response

```json
{
  "access_token":NEW_ACCESS_TOKEN,
  "expires_in":3600,
  "token_type":"bearer",
  "scope":"user",
  "refresh_token":"NEW_REFRESH_TOKEN"
}
```

Let’s not fail or using the access_token

Remember our failed attempt to request an API at the beginning of the article? Let’s try again.
Request

```
symfony.dev/api/articles?access_token=ACCESS_TOKEN
```

#### Response

```json
["article1","article2","article3"]
```

Seems like a proper response, does it?
Let’s try the other request, which is supposed to maintain user session with the access_token.
#### Request

```
symfony.dev/api/user?access_token=ACCESS_TOKEN
```

If you obtained your access_token through Authorization Code, Implicit Grant or Password, you should see a JSON representation of the user object.

```json
{
  "id":1,
  "username":"user1"
}
```

If that was the Client Credentials, `ACCESS_TOKEN` doesn’t contain any user information association with it, therefore there can’t be any user information retrieved, as the response suggests

```json
{"message":"User is not identified"}
```



## Customize

If you want to add optionnals containers like Redis, PHPMyAdmin... take a look on [doc/custom.md](doc/custom.md).

## How it works?

Have a look at the `docker-compose.yml` file, here are the `docker-compose` built images:

* `db`: This is the MySQL database container,
* `php`: This is the PHP-FPM container in which the application volume is mounted,
* `nginx`: This is the Nginx webserver container in which application volume is mounted too,
* `elk`: This is a ELK stack container which uses Logstash to collect logs, send them into Elasticsearch and visualize them with Kibana.

This results in the following running containers:

```bash
$ docker-compose ps
           Name                          Command               State              Ports            
--------------------------------------------------------------------------------------------------
dockersymfony_db_1            /entrypoint.sh mysqld            Up      0.0.0.0:3306->3306/tcp      
dockersymfony_elk_1           /usr/bin/supervisord -n -c ...   Up      0.0.0.0:81->80/tcp          
dockersymfony_nginx_1         nginx                            Up      443/tcp, 0.0.0.0:80->80/tcp
dockersymfony_php_1           php-fpm                          Up      0.0.0.0:9000->9000/tcp      
```

## Useful commands

```bash
# bash commands
$ docker-compose exec php bash

# Composer (e.g. composer update)
$ docker-compose exec php composer update

# SF commands (Tips: there is an alias inside php container)
$ docker-compose exec php php /var/www/symfony/app/console cache:clear # Symfony2
$ docker-compose exec php php /var/www/symfony/bin/console cache:clear # Symfony3
# Same command by using alias
$ docker-compose exec php bash
$ sf cache:clear

# Retrieve an IP Address (here for the nginx container)
$ docker inspect --format '{{ .NetworkSettings.Networks.dockersymfony_default.IPAddress }}' $(docker ps -f name=nginx -q)
$ docker inspect $(docker ps -f name=nginx -q) | grep IPAddress

# MySQL commands
$ docker-compose exec db mysql -uroot -p"root"

# F***ing cache/logs folder
$ sudo chmod -R 777 app/cache app/logs # Symfony2
$ sudo chmod -R 777 var/cache var/logs # Symfony3

# Check CPU consumption
$ docker stats $(docker inspect -f "{{ .Name }}" $(docker ps -q))

# Delete all containers
$ docker rm $(docker ps -aq)

# Delete all images
$ docker rmi $(docker images -q)
```

## FAQ

* Got this error: `ERROR: Couldn't connect to Docker daemon at http+docker://localunixsocket - is it running?
If it's at a non-standard location, specify the URL with the DOCKER_HOST environment variable.` ?  
Run `docker-compose up -d` instead.

* Permission problem? See [this doc (Setting up Permission)](http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup)

* How to config Xdebug?
Xdebug is configured out of the box!
Just config your IDE to connect port  `9001` and id key `PHPSTORM`

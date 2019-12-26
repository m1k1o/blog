# blog
This is simple self-hosted lightweight singe-user PHP blog where you can create your own facebook-like feed, give view access to other people and you can share rich text with photos, highlighted code or links.

In this context, lightweight means:
* No npm dependency, there won't be annoying 1GB `node_modules` folder.
* No pipeline. What you see is pure developed code without need to install it.
* No overhead. Just essential features. Simple usage.

## Install standalone app using `docker-compose`
You need to have installed [docker-compose](https://docs.docker.com/compose/install/).

### Step 1: Clone this repository:
```
git clone https://github.com/m1k1o/blog
cd blog
```

### Step 2: Build & run containers using docker-compose.
```
docker-compose up -d
```

You can specify these environment variables, otherwise there will be used default values:
* **HTTP_PORT=80** - where will be blog accessible.
* **HTTPS_PORT=443** - if you want to use HTTPS.
* **DATA=./data** - directory, where will be stored user data.

These environment variables can be stored in `.env` file or passed to command directly:
```
HTTP_PORT=3001 HTTPS_PORT=3002 DATA=/home/user/blog docker-compose up -d
```

### Step 3: Copy config
Copy config from root folder to your new `./data/` folder.

```
cp ./config.ini ./data/config.ini
```

Now you can modify your config.

### Correct permissions
Make sure your `./data/` folder has correct permissions. Apache is running as `www-data` user, that needs to have write access to `./data/` folder (when you upload images).

#### Good solution
Change folder owner to `www-data`:

```
chown 33:33 ./data/
```

Alternative: Add `www-data` user to user group, that owns  `./data/` folder.

#### Bad solution (but it works)
Set `777` permission for your `./data/`, so everyone can read, write and execute:

```
chmod 777 ./data/
```

**NOTICE:** You should not use `777`, you are giving anyone access to this folder. Maybe to some attacker, who can run his exploit here.

## Install using docker
You need to have installed [docker](https://docs.docker.com/install/).

If you don't want do spawn new database server, but you want to use your existing `mariadb` or `mysql` server, you can install this blog using Docker.

### Build image
After you cloned and accessed repository, you run this command. It will vuild docker image and assign tag `blog`.
```
docker build --tag blog .
```

### Run container
After you built image, you can run it like this:

```
docker run \
  -p 80:80 \
  -p 443:443 \
  -v ./data:/var/www/html/data \
  blog
```

Now you can copy config to your new `./data` folder and set up database connection settings.

```
cp ./config.ini ./data/config.ini
```

## Install
If you decied you don't want to use docker, you can intall it manually.

**Requirements:** Apache 2.0*, PHP 7.4, MariaDB 10.1

**NOTICE:** If you would like to use nginx or another web server instead, make sure sensitive data are not exposed to public. Since `.htaccess` is protecting those files in Apache, that could not be the case in different environments. Take care of:
* **config.ini** - disallow access to all *.ini* files for public.
* **data/logs/_ANY_.log** - make sure no sensitive informations are in *.log*.

### Database Schema
You can find database schema in `./app/db/01_schema.sql`.

### Debug mode
You would like to turn on debug mode (in config add `debug = true`) to see, if your server is set up correctly. In debug mode, you will be told if you are missing some **PHP extensions** needed to be installed on your server.

## Config file
**DON'T** edit `./config.ini` file, instead copy it to `./data/config.ini` folder and edit it there.

**But, why?** If there is any change in config file (most likely adding a new feature), you will have problems with merging new version. Also, if you would fork this repository, you might accidentally push your secrets to git. We don't want that to happen. Content of folder `/data` is ignored by git, so none of your pictures or personal data should ever be pushed to git.

# blog
This is a simple self-hosted, lightweight, singe-user PHP blog, where you can create your own Facebook-like feed. Give read access to other people, and you can share rich text with photos including highlighted code or links.

In this context lightweight means:
* No npm dependency, there won't be an annoying 1GB `node_modules` directory.
* No pipeline. What you see is pure code without a need to install it.
* No overhead, essential features, simple usage.

## Install standalone app using `docker-compose`
You need to install [docker-compose](https://docs.docker.com/compose/install/).

### Step 1: Clone this repository:
```
git clone https://github.com/m1k1o/blog
cd blog
```

### Step 2: Build & run containers using docker-compose.
```
docker-compose up -d
```

You can specify these environment variables, otherwise the default ones will be used:
* **HTTP_PORT=80** - where the blog will be accessible.
* **HTTPS_PORT=443** - if you want to use with HTTPS.
* **DATA=./data** - directory to store the user data.

These environment variables can be stored in the `.env` file or passed to the command directly:
```
HTTP_PORT=3001 HTTPS_PORT=3002 DATA=/home/user/blog docker-compose up -d
```

### Step 3: Copy the config
Copy the config from the root directory to your new `./data/` directory.

```
cp ./config.ini ./data/config.ini
```

Now you can modify your config.

### Correct permissions
Make sure your `./data/` directory has correct permissions. Apache is running as a `www-data` user, which needs to have write access to the `./data/` directory (for uploading images).

#### Prefered solution
Change the directory owner to the `www-data` user:

```
chown 33:33 ./data/
```

Alternatively, add the `www-data` user to the user group that owns the `./data/` directory.

#### Bad solution (but it works)
Set `777` permission for your `./data/`, so everyone can read, write, and execute:

```
chmod 777 ./data/
```

**NOTICE:** You should not use `777`. You are giving access to anyone for this directory. Maybe to some attacker, who can run his exploit here.

## Install using docker
You need to install [docker](https://docs.docker.com/install/).

If you don't want do spawn a new database server, but you want to use your existing `mariadb` or `mysql` server, you can install this blog using Docker.

### Build image
After you have cloned and accessed the repository, you need to run this command. It will build a docker image with a tag `blog`.
```
docker build --tag blog .
```

### Run container
After you have built the image, you can run it as the following:

```
docker run \
  -p 80:80 \
  -p 443:443 \
  -v ./data:/var/www/html/data \
  blog
```

Now you can copy the config to your new `./data` directory and set up the database connection settings.

```
cp ./config.ini ./data/config.ini
```

## Install
If you have decied that you don't want to use Docker, you can intall it manually.

**Requirements:** Apache 2.0*, PHP 7.4, MariaDB 10.1

**NOTICE:** If you would like to use Nginx or another web server, make sure that the sensitive data are not exposed to the public. Since `.htaccess` is protecting those files in Apache, that could not be the case in a different environment. Take care of:
* **config.ini** - disallow access to all *.ini* files for the public.
* **data/logs/\_ANY_.log** - make sure no sensitive information are located in *.log*.

### Database Schema
You can find database schema in the `./app/db/01_schema.sql` file.

### Debug mode
To check if your server is set up correctly, turn on a debug mode (in config add `debug = true`) to see the details. In the debug mode, an error may be shown if you are missing some **PHP extensions** needed to be installed on your server.

## Config file
**DO NOT** edit `./config.ini` file. If you wish to modify the config, simply make a copy to the `./data/config.ini` directory and edit it there.

**But, why?** If there is any change in config file (most likely adding a new feature), you will have problems with merging a new version. Also, if you would fork this repository, you might accidentally push your secrets to the git. We don't want that to happen. Content of the `/data` directory is ignored by the git, so none of your pictures or personal data should ever be published to git.

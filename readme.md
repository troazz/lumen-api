# Lumen API
## Simple Lumen API with it's testing

How to install:
- clone this repo `git clone git@github.com:troazz/lumen-api.git`
- enter application directory `cd lumen-api`
- install composer packages `composer install`
- copy .env file `cp .env.example .env`
- setup your database setting in file *.env*
- migrate all tables `artisan php migrate`
- insert fake data with seeder `artisan php db:seed` [OPTIONAL]
- Voila! Done, you can run unit testing `./vendor/bin/phpunit` and try APIs in postman

Credential for basic auth:
- **Email**: admin@here.com
- **Password**: password
* Note: all seeded users using password *password*
<p align="center"><a href="#" target="_blank"><img src="" alt="Asaka image" width="400"></a></p>

# Getting Started
## About Asaka
write about Asaka project here <br/>
## Project Setup 

<br/>

### Caution
<br/>
<b>While installing dependency with composer please run composer install command</b>. composer install command downloads dependency from composer.lock file. For local, test, staging or production enviroment composer.lock file needs to be the same or else you might have unexpected bugs in environments due to different versions of dependency.
<br/>
<b>Do not run composer update command as it will download latest version of dependency and might break project</b>. composer update command reads from composer.json file and downloads latest version of dependencies. This will rewrite your composer.lock file.
<br/>

### System requirements
<br/>
<p>This project is done using laravel framework, Please check the official laravel installation guide for server requirements.</p>
<p>Framework : Laravel</p>
<p>Framework Version : v8.x</p>
<p>Database :  MySql </p>
<p>Server : Apache</p>

In order to run this project smoothly, Following details required
| Constants | Description |  |
| --- | --- | --- |
| APP_NAME | Asaka API |  |
|APP_URL | Production or staging server url| |
|AWS_ACCESS_KEY_ID | Aws access key | |
|AWS_SECRET_ACCESS_KEY | Aws secret key | |
|AWS_DEFAULT_REGION | Default Region | |
|AWS_BUCKET | Bucket details | |
|AWS_ENDPOINT | Aws end point | |
|AWS_URL | Aws url | | 

The api can be accessed at [http://localhost:8000/api](http://localhost:8000/api).

-------------
## Dependencies

--[fruitcake/laravel-cors](https://github.com/fruitcake/laravel-cors)
--[dyrynda/laravel-cascade-soft-deletes](https://github.com/michaeldyrynda/laravel-cascade-soft-deletes)

---------------
## Folders

- `app` - Contains all the Eloquent models
- `app/Http/Controllers/Api` - Contains all the api controllers
- `app/Models/` - Contains Model class files
- `app/Http/Middleware` - Contains the auth middleware
- `config` - Contains all the application configuration files
- `database/factories` - Contains the model factory for all the models
- `database/migrations` - Contains all the database migrations
- `routes/api` - Contains all the api routes defined in api.php file
- `tests/Feature/Api` - Contains all the api tests

---------------
## Environment variables

- `.env` - Environment variables can be set in this file

***Note*** : You can quickly set the database information and other variables in this file and have the application fully working.

--------------
# Testing API

Run the laravel development server

    php artisan serve

The api can now be accessed at

    http://localhost:8000/api

Request headers

| **Required** 	| **Key**              	| **Value**            	|
|----------	|------------------	|------------------	|
| Yes      	| Content-Type     	| application/json 	|
| Yes      	|	|   	|
| Yes 	| 	|      	|


## Code of Conduct
Terms and cosditions or copywrite comes here
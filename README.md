[![Licence: MIT](https://img.shields.io/badge/Licence-MIT-green.svg)](https://opensource.org/licences/MIT)
[![(GitHub release](https://img.shields.io/github/release/Fernand197/PHPMini.svg)](https://GitHub.com/Fernand197/PHPMini/releases/)
[![(GitHub tag](https://img.shields.io/github/tag/Fernand197/PHPMini.svg)](https://GitHub.com/Fernand197/PHPMini/tags/)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![MYSQL](https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white)
![Postgres](https://img.shields.io/badge/postgres-%23316192.svg?style=for-the-badge&logo=postgresql&logoColor=white)
![SQLite](https://img.shields.io/badge/sqlite-%2307405e.svg?style=for-the-badge&logo=sqlite&logoColor=white)

# PHPMini

PHPMini is a small php framework based on mvc architecture that have a similar structure and syntax 
like [Laravel](https://laravel.com).

## Table of content

1. Routes
   - web routes
   - api routes
   - scopes routes
   - controllers routes
   - resources routes
   - api resources routes
2. Controllers
   - create controller
   - dispatch request to controller
   - automatically escape models on controllers
3. Views
   - render a view
   - render a view with params
   - render a view with layout
4. Models
   - create a model
5. ORM


## Installation

You can clone the repository on github with the command

```cmd
git clone https://github.com/Fernand197/PHPMini.git
```
Create a **.env** file at root of project and copie the content of **.env.example** file and paste in it.

## Configuration

Set up these environments variables in the **.env** file to configure the database.  
Supported database are MYSQL, Postgresql and Sqlite.

```env
DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

## Routes

* Web routes
 
You can define web routes in **routes/web.php** using different methods like get, post, put, patch, delete and options.  
The methods take 2 parameters: **uri** and **action**. **uri** must be a string and **action** can be a string, an 
array or a callable.

```php
$router->get("/welcome", "App\Http\Controllers\HomeController@welcome");

$router->get("/welcome", [HomeController::class, "welcome"]);

$router->get("/welcome", function(){
    echo "Hello World";
});

```

* Api routes

api routes are defined like web routes but in **routes/api.php**

```php
$router->api()->group(function () use ($router){
    # define yours api routes here!
    
    // the uri of this route will be "/api/welcome"
    $router->get("/welcome", function() {
        echo "welcome"
    });
})
```

you can change the base name of your api routes like this.

```php
$router->api("your_basename_api_route")


// exemple
$router->api("apiV2")->group(function () use ($router){
    # define yours api routes here!
    
    // the uri of this route will be "/apiV2/welcome"
    $router->get("/welcome", function() {
        echo "welcome"
    });
})
```

You can define route with parameters  
Parameter can be a regular expression, a primary key for a model or just a simple parameter.  
The number of parameters correspond to the number of parameters passed in the callable or the controller method.

```php
// uri here can be "/welcome/john" 
$router->get("/welcome/{name}", function($name){
    echo "Welcome " . $name;
});

// uri here can be "users/1"
$router->get("/users/{id}", function($id){
    echo "ID : " . $id
});

/* 
param "user" can only be the primary key of the User model
this primary key is casted in the callable or in the controller  
method to the User model corresponding to that 
primary key
*/
$router->get("/users/{user}", function(User $user){
    var_dump($user);
});

// param here can only be digit
$router->get("/user/(\d+)", function(User $user){
    var_dump($user);
});

```
By default Request are dispatched in callable and in the controller method.

```php
$router->get("/welcome", function(Request $request){
    var_dump($request);
});

// controller method
public function welcome(Request $request){
    var_dump($request);
}
```

* Scoped routes

You can define routes that have the same sub-uri like this.

```php
$router->scope("/users")->group(function() use ($router) {

    // uri here is "/users/{user}"
    $router->get("{user}", [UserController::class, "show"]);

    // uri here is "/users/{user}/posts/{post}"
    $router->get("{user}/posts/{post}", [UserController::class, "show"]);
});
```

* Controllers Routes

They are uses to define routes that based the same controller.

```php
$router->controller(UserController::class)->group(function() use ($router){
    
    $router->get("/users", "index");
    $router->post("/users", "store");
    $router->get("/users/{user}", "show");
    $router->patch("/users/{user}", "update");
    $router->delete("/users/{user}", "delete");
})
```

* Resources routes

Use to define routes with methods controller like **index**, **store**, **create**, **edit**, **show**, **update** 
and **delete**. It takes 2 params: an **uri** and a **controller**

```php
// define routes with these all methods
$router->resource("/users", User::class)->all();

     // execution code behind
    $router->get("/users", "index");
    $router->post("/users", "store");
    $router->get("/users/create", "create");
    $router->get("/users/{user}", "show");
    $router->patch("/users/{user}", "update");
    $router->get("/users/{user}/edit", "edit");
    $router->delete("/users/{user}", "delete");

// define routes with only index and show methods
$router->resource("/users", User::class)->only(["index", "show"]);

    // excution code behind
    $router->get("/users", "index");    
    $router->get("/users/{user}", "show");

//define routes with all methods except index and show
$router->resource("/users", User::class)->except(["index", "show"]);

    // excution code behind
    $router->post("/users", "store");
    $router->get("/users/create", "create");
    $router->patch("/users/{user}", "update");
    $router->get("/users/{user}/edit", "edit");
    $router->delete("/users/{user}", "delete");

```

* Api resources routes

define routes for an api. It is same like resources routes but don't implement routes with methods **create** and 
**edit**.

```php
$router->apiResource("/users", UserConstroller::class);
```

* Apis resources routes
 
To create multiple api resources routes

```php
$router->apiResources([
    "/posts" => PostController::class,
    "/users" => User::controller::class
]);
```

## Controllers

* Create controller

In the **app/Http/Controller** directory you can create all your controller.

```php
# HomeController

namespace App\Http\Controllers;

class HomeController extends Controller
{
    # put yours controller methods here
}
```

You can access Request in Controller methods. you must pass it as the first parameter.

```php
public function index(Request $request)
{
    var_dump($request);
}
```

You can access to the escaped model in controller model.

```php
// for the route uri "/users/{user}" you can acess the model corresponding to the "user" param

public function show(User $user)
{
    var_dump($user);
}
```

## Views

* Render a View

To do it you can use the helpers function **view**. This function takes 3 parameters: the view file location 
separated by comma (ex: "user.delete" correspond to "user/delete.php" file), an array context to specify some 
variable that will be accessing on the view and bool a layout specify if the view is base on a layout.   
View files are located in the **resources/views** directory

```php
// in route
$router->get("/welcome", function(){
    return view("welcome");
})

// in controller
public function show()
{
    return view("users.create");
}
```

* Render a view with params

```php
// in route
$router->get("/welcome", function(){
    $username = "John";
    return view("welcome", compact("username"));
})

// in controller
public function show()
{
    $username = "Doe";
    return view("users.create", compact("username"));
}
```

you can access these on the view.

```php
# welcome.php

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>welcome</title>
</head>

<body>
    Username: <?= $username ?><br>
</body>

</html>
```

* Render view with layout

You must create a file for layout in **resources/views**. you can call it layout.php

```php
# layout.php

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>welcome</title>
</head>

<body>
    <?= $content ?>
</body>

</html>

// controller method
public function show()
{
    return view("welcome", [], "layout");
}

// welcome.php
$content = """HTML

    <b> My welcome view base on a layout <b>

HTML;
```

## Model

models are located in the **app/Http/models** directory. you can create your model like this and specify the table 
name referenced in the database. you can also specify primary key by default it "id".

```php
<?php

namespace App\Models;

use PHPMini\Models\Model;

class User extends Model
{
    protected static $table = 'users';
    protected static $primary_key = "pk";
}

```

## ORM

PHPMini provided a powerful ORM.

- get all the database entities of specific model

```php
User::all();
```

- find a model buy primary keys

```php
// find user by id 1;
User::find(1);
```

- where condition

```php
// get all users where status is active
User::where("status", "active")->get();

// get all users that id is greater than 8
User::where("id", ">", 8)->get();

// get the first that email is johndoe@gmail.com and username is john
User::where([
    "email" => "johndoe@gmail.com",
    "username" => "john";
])->first();

// get the 10 first users that id is greated than 2;
User::where("id", ">", 2)->limit(10)->get();

// get user where email is john@gmail.com or if not found return nothing
User::where("email", "john@gmail.com")->firstOr(function(){
    return "nothing";
});

// get all users that id equal or greater than 8 or id equal 1
User::where('id', '>=', 8)
            ->orWhere('id', 1)
            ->get();

// you can chaining where conditions
User::where('id', '>=', 8)
            ->andWhere('username', "john")
            ->andWhere('email', "john@gmail.com")
            ->get();
```

- create method

```php
User::create([
    "email" => "john@gmail.com";
    "username" => "john";
]);
```

- update method.

```php

// do it on a specific model
$user->update([
    "email" => "update@email.com"
]);
```

- delete method

```php
$user->delete();
```

- findOr

find a model or do any thing.

```php
// find user with id 1 or return "nothing"
User::findOr(1, function(){
    return "nothing";
});
```

- updateOrCreate

update a model or create if not found corresponding 

```php
// if the isn't user with username john and email john@gmail.com create a user with these values and phone 12345784
User::updateOrCreate(['username' => 'john', 'email' => 'john@gmail.com'], ['phone' => '12345784']);
```

- delete multiple models by primary keys

```php

// delete users with id 1, 2, and 10
User::destroy([1, 2, 10]);
```

- delete all the models

```php
User::truncate();
```

## Contributions

- Fork this repository

Fork this repository by clicking on the fork button on the top of this page.  
This will create a copy of this repository in your account.

- Clone the repository 

Now clone the forked repository to your machine. Go to your GitHub account, open the forked repository, click on the code button and then click the copy to clipboard icon.

Open a terminal and run the following git command:

```cmd
git clone https://github.com/Fernand197/PHPMini.git
```

- Follow the step for the configuration at the top


- Create a branch

Change to the repository directory on your computer (if you are not already there):

```cmd
cd PHPMini
```

Now create a branch using the git `checkout` command:

```cmd
git checkout -b your-new-branch-name
```

example 


```cmd
git checkout -b john
```

- Make necessary changes and commit those changes

commit those changes using the git `commit` command:

```cmd
git commit -m "add your message"
```

- Push change to GitHub

```cmd
git push origin <add-your-branch-name>
```

replacing `<add-your-branch-name>` with the name of the branch you created earlier.

- Submit your changes for review

If you go to your repository on GitHub, you'll see a `Compare & pull request` button. Click on that button.  
Now submit the pull 

Soon I'll be merging all your changes into the master branch of this project.

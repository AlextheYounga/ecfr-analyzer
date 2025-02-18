# Developer Notes


## Getting Started
> I will make all of this 100x smoother I promise
> 
**Clone the repository**
```sh
git clone [your-repo-url] ecfr-analyzer
cd ecfr-analyzer
```

**Set env file**
`cp .env.example .env`


**Install PHP dependencies**
`composer install`

**Install Node.js dependencies**
```sh
nvm install # optional, if you're using nvm
npm install
```

**Copy environment file and generate key**
`cp .env.example .env`


**Configure SQLite database**
```sh
php artisan key:generate
php artisan migrate
```

Or just steal my DB: [Proton Drive Link](https://drive.proton.me/urls/Q7PE08B3M4#Frd16STAW4cu)

**Compile Rust scripts**
```sh
cd rust
# Build Rust executables
cargo build --release
```

**Download Data**
You can either do it yourself:

```sh
  ecfr:titles               Download eCFR titles from API
  ecfr:entities             Download eCFR structures from API and convert them to "title entities"
  ecfr:agencies             Download eCFR agencies from API
  ecfr:documents            Fetch latest title documents from ECFR
  ecfr:content              Parse the title documents and save the Markdown content to the database
  ecfr:agency-titles        Save agency title entity relations to the database
  ecfr:agency-words         Calculate the word count for each agency
```

Or just steal my DB: [Proton Drive Link](https://drive.proton.me/urls/Q7PE08B3M4#Frd16STAW4cu)

**Start up Frontend**
`npm run dev` Start the node server

`php artisan serve` Start the php server


## Project Structure
```
├── app
│   ├── Console
│   │   └── commands 			-> Callable commands using `php artisan [command]`
│   ├── Http
│   │   └── Controllers 		-> App server logic
│   ├── Jobs 					-> Cron jobs (WIP)
│   ├── Models 					-> Database models (contains database relation logic, get/fetch logic, etc)
│   └── Services
│       └── ECFRService.php 	-> API handler for eCFR
├── composer.json 				-> Contains all PHP packages
├── config 						-> All app configuration settings (mostly boilerplate)
├── database
│   ├── migrations 				-> Database migrations folder (with some boilerplate migrations). `php artisan migrate` to run.
│   └── seeders 				-> Database seeders (not being used currently). Can run with `php artisan db:seed`
├── resources	
│   ├── js
│   │   ├── app.js 				-> Javascript entrypoint
│   │   └── Pages 				-> All Vue templates are here
│   └── views
│       └── app.blade.php 		-> Html entrypoint
├── routes
│   └── web.php 				-> All routing logic
├── rust 						-> Rust scripts for parsing ecfr documents.
│   ├── Cargo.toml 				-> Where to put Rust crates. `cargo build --release`
│   ├── snippets.md 			-> Just random notes I was taking.
│   └── src 					-> Where the Rust scripts are located
├── scripts 					-> Random one-off scripts. Will probably remove at some point.
├── storage 					-> IMPORTANT: This is where I am storing all the ecfr documents for processing
│   ├── app
│   │   └── private	 			-> All ecfr documents get stored under this folder. Default location for Laravel's Storage helper.
├── tests 						-> No tests currently.
└── vite.config.js 				-> Vite server config.
`
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

**Download Data**
```sh
php artisan ecfr:titles
php artisan ecfr:agencies
php artisan ecfr:structures
php artisan ecfr:documents
```

**Compile Rust scripts**
```sh
cd rust
# Build Rust executables
cargo build --release
```

**Run Scripts**
Support both fully nested, flat, and full document storage structures:
  - *Nested:* `title-1/chapter/subchapter/part/section.md`
  - *Flat:* `title-1/section_id.md`
  - *Full* `title-1.md`

Run Rust Script. Options: [nested, flat, full]

`./rust/target/release/title_markdown_parser [option]`

**Run Word Analyzer**
> IMPORTANT: Must have previously run the rust script with the **full** option at least once
`php artisan ecfr:words`

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
# BAdle

Daily Blue Archive quiz inspired by Wordle/Loldle.

## How to setup

1. `git clone https://github.com/zolizoli727/BAdle.git` the repo and make a .env file using the .env.example
2. `composer install && npm install` inside project root
3. `php artisan key:generate`
4. `php artisan migrate --seed`
5. run `npm run dev` and `php artisan serve`

Then visit `http://localhost:8000` to play. set the user_level value to 2 for the debug controls inside the user table in db.

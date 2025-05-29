cache:
		php artisan optimize:clear
		php artisan icons:cache
		php artisan icon:cache
		php artisan filament:cache-components
		php artisan filament:cache-component


fresh:
        php artisan migrate:fresh --seed

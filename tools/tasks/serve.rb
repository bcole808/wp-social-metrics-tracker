desc "Starts a web server for development"
task :serve do

  puts 'Now opening the browser for your convenience...'

  # Open browser for developer! :)
  system "open http://#{$options['dev_url']}/wp-admin/"

  # Start php server
  exec "php -S #{$options['dev_url']} -t tools/wordpress/ tools/router.php"

end
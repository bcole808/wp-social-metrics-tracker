desc 'Runs integration and unit tests'
task :test do

  Rake::Task["test:before"].invoke

  # Run tests
  pass = system 'phpunit --exclude-group external-http'

  Rake::Task["test:after"].invoke

  exit 1 if !pass

end

namespace :test do

  task :before do 

    # Switch WP Config to test mode
    print "Adjusting wp-config.php..."
    wp_config = File.read('tmp/wp-config.php')
    new_contents = wp_config.gsub($options['db_name_for_dev'], $options['db_name_for_test'])
    File.open('tmp/wordpress/wp-config.php', "w") {|file| file.puts new_contents }
    puts "Done!"

    # Start the selenium server
    print "Starting Selenium server..."
    @selenium_pid = Process.spawn("java -jar ./tmp/selenium/selenium-server-standalone-2.46.0.jar ", :out=>"/dev/null")
    puts "Done!"

    # Start PHP server
    print "Starting PHP server..."
    @php_pid = Process.spawn("php -S #{$options['test_url']} -t tmp/wordpress tools/router.php")
    puts "Done! [PID #{@php_pid}]"

  end

  task :install_db => :requires_wpcli do 
    # Fill DB with stuff
    system "#{@wpcli} core install \
      --url=http://#{$options['test_url']} \
      --title='SMT test site' \
      --admin_user=#{$options['wp_user']} \
      --admin_password=#{$options['wp_password']} \
      --admin_email=#{$options['wp_email']}"
  end

  task :after do

    # remove WP Config test mode
    print "Removed wp-config.php..."
    File.delete('tmp/wordpress/wp-config.php')
    puts "Done!"

    # Close PHP server
    print "Closing PHP server..."
    Process.kill "TERM", @php_pid
    Process.wait @php_pid
    puts "Done!"

    # Close the selenium server
    print "Closing Selenium server..."
    Process.kill "TERM", @selenium_pid
    Process.wait @selenium_pid
    puts "Done!"

  end

end


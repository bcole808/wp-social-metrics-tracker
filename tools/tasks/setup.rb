namespace :setup do

  ##################################
  desc 'Creates a WP db'
  ##################################
  task :tests do
    print_step('Downloading and configuring WordPress core and test files')

    system "bash tests/bin/install-wp-tests.sh \
      #{$options['db_name_for_test']} \
      #{$options['db_username']} \
      #{$options['db_password']} \
      #{$options['db_host']} \
      #{$options['wp_version']}
    "

  end

  ##################################
  desc 'Creates a WP db'
  ##################################
  task :dev_db => :requires_wpcli do
    print_step('Creating WP dev db')

    # Check if WP is already installed
    if system "if ! $(wp core is-installed); then \nexit 1 \nfi"
      puts "\n\n*****\nWordPress is already installed. Drop the database and re-install with WordPress #{$options['wp_version']}?".colorize(:red)
      
      system "mysqladmin drop #{$options['db_name_for_dev']} \
        --user=#{$options['db_username']} \
        --password=#{$options['db_password']}"

    end

    # Create empty dev DB
    system "mysqladmin create #{$options['db_name_for_dev']} \
      --user=#{$options['db_username']} \
      --password=#{$options['db_password']}"

    # Fill DB with stuff
    system "wp core install \
      --url=http://#{$options['dev_url']} \
      --title='SMT test site' \
      --admin_user=#{$options['wp_user']} \
      --admin_password=#{$options['wp_password']} \
      --admin_email=#{$options['wp_email']}"

  end

  ##################################
  desc 'Create wp-config for dev'
  ##################################
  task :wp_config => :requires_wpcli do
    print_step('Creating wp-config for DEV')

    `rm -f tmp/wp-config.php`
    `rm -f tmp/wordpress/wp-config.php`

    system "wp core config \
      --skip-salts \
      --dbname=#{$options['db_name_for_dev']} \
      --dbuser=#{$options['db_username']} \
      --dbpass=#{$options['db_password']} \
      --dbhost=#{$options['db_host']}"

    system "mv tmp/wordpress/wp-config.php tmp/wp-config.php"
  end

  desc 'Installs the SMT plugin as a symlink'
  task :install_plugin => :requires_wpcli do
    print_step('Activating SMT plugin on DEV')

    plugin_dir = 'tmp/wordpress/wp-content/plugins/social-metrics-tracker'

    # Symlink to ./src
    system "ln -s ../../../../src #{plugin_dir}"
    puts "Created a symbolic link at #{plugin_dir}"

    # Activate plugin
    system "wp plugin activate social-metrics-tracker"
  end

end


##################################
desc 'Downloads and configures WordPress and test suite'
##################################
task :setup do 

  Rake::Task["setup:tests"].invoke
  Rake::Task["setup:wp_config"].invoke
  Rake::Task["setup:dev_db"].invoke
  Rake::Task["setup:install_plugin"].invoke

  print_success "Setup done! Run 'rake serve' to spin up a server!"
end
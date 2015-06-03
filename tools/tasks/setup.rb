namespace :setup do

  ##################################
  desc 'Download and install WP core'
  ##################################
  task :wp_core => :requires_wpcli do
    system "rm -rf tools/wordpress"
    system "wp core download --version=#{$options['wp_version']}"
  end

  ##################################
  desc 'Download WP test lib'
  ##################################
  task :wp_test_suite => :requires_wpcli do
    puts "Setting up test suite"
  end

  ##################################
  desc 'Creates a WP db and activates plugin'
  ##################################
  task :db => :requires_wpcli do

    # Check if WP is already installed
    if system "if ! $(wp core is-installed); then \nexit 1 \nfi"
      puts "\n\n*****\nWordPress is already installed. Drop the database and re-install with WordPress #{$options['wp_version']}?"
      system "mysqladmin drop #{$options['db_name_for_dev']} --user=#{$options['db_username']} --password=#{$options['db_password']}"
    end

    # Create empty DB
    system "mysqladmin create #{$options['db_name_for_dev']} --user=#{$options['db_username']} --password=#{$options['db_password']}"

    # Fill DB with stuff
    system "wp core install --url=http://#{$options['dev_url']} --title='SMT test site' --admin_user=#{$options['wp_user']} --admin_password=#{$options['wp_password']} --admin_email=#{$options['wp_email']}"

  end

  ##################################
  desc 'Create wp-config for dev and test'
  ##################################
  task :wp_config => :requires_wpcli do
    system "wp core config --dbname=#{$options['db_name_for_dev']} --dbuser=#{$options['db_username']} --dbpass=#{$options['db_password']} --dbhost=#{$options['db_host']}"
  end

  desc 'Installs the SMT plugin as a symlink'
  task :install_plugin => :requires_wpcli do
    plugin_dir = 'tools/wordpress/wp-content/plugins/social-metrics-tracker'

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
  Rake::Task["setup:wp_core"].invoke
  Rake::Task["setup:wp_test_suite"].invoke
  Rake::Task["setup:wp_config"].invoke
  Rake::Task["setup:db"].invoke
  Rake::Task["setup:install_plugin"].invoke

  print_success "Setup done! Run 'rake serve' to spin up a server!"
end
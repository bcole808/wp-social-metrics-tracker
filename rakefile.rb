
require 'yaml'
require './tools/helpers.rb'
include Helpers

# Load default config
$options = YAML.load_file('config.defaults.yml')

# Load ENV config
$options['db_username'] = ENV['MYSQL_USER']     if ENV['MYSQL_USER']
$options['db_password'] = ENV['MYSQL_PASSWORD'] if ENV['MYSQL_PASSWORD']
$options['db_name_for_dev']  = ENV['db_name_for_dev']  if ENV['db_name_for_dev']
$options['db_name_for_test'] = ENV['db_name_for_test'] if ENV['db_name_for_test']

# Merge local config
$options = $options.merge(YAML.load_file('config.yml')) if File.file?('config.yml')

# This task ensures WP-CLI is installed
task :requires_wpcli do

  # Try composer WP-CLI
  if system './vendor/bin/wp cli version'
    @wpcli = './vendor/bin/wp'
    puts "Using composer WP-CLI in ./vendor/bin/wp"

  # Try system WP-CLI
  elsif system 'wp cli version'
    @wpcli = 'wp'
    puts "Using system WP-CLI command 'wp'"

  end

  # Fail if not found
  if !@wpcli
    print_warning("Please first install WP-CLI from http://wp-cli.org") 
    fail "WP-CLI is not installed."
  end

end

# Load tasks from 'tools/tasks'
Dir["tools/tasks/*.rb"].each {|file| load file }

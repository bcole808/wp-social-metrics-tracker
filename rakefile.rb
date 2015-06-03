
require 'yaml'
require './tools/helpers.rb'
include Helpers

# Load default config
$options = YAML.load_file('config.defaults.yml')

# Merge local config
$options = $options.merge(YAML.load_file('config.yml')) if File.file?('config.yml')

# This task ensures WP-CLI is installed
task :requires_wpcli do
  if !system 'wp cli version'
    print_warning("Please first install WP-CLI from http://wp-cli.org") 
    fail "WP-CLI is not installed."
  end
end

# Load tasks from 'tools/tasks'
Dir["tools/tasks/*.rb"].each {|file| load file }

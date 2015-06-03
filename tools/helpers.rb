require 'colorize'

module Helpers

  # Prints a big message to the terminal
  def print_success(msg)
    puts ''
    puts '========= SUCCESS ========='.colorize(:green)
    puts msg.colorize(:green)
    puts '==========================='.colorize(:green)
    puts ''
  end

  # Prints a big message to the terminal
  def print_warning(msg)
    puts ''
    puts '========! WARNING !========'.colorize(:yellow)
    puts msg.colorize(:yellow)
    puts '==========================='.colorize(:yellow)
    puts ''
  end

  # Prints a big message to the terminal
  def print_info(msg)
    puts msg
  end

  # Displays a message and waits for input
  # @return string - the user input
  def get_input(msg)
    print msg.colorize(:white).colorize( :background => :red)
    STDIN.gets.strip
  end

  # Displays a message and waits for input, unless environment variable to override prompts is set
  # @return boolean - if the user confirms yes
  def confirm(msg)
    if ENV['SKIP_CONFIRM'] == '1'
      true
    else 
      puts ''
      get_input(msg + ' (y/n)').downcase == 'y'
    end
  end

end
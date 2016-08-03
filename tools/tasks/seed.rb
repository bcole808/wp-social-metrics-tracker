require 'open-uri'

namespace :seed do

  task :reddit => :requires_wpcli do

    # Get data from Reddit
    # use custom user agent because reddit throttles unique user agents
    response = open('https://www.reddit.com/rising.json', 'User-Agent' => 'wp-social-metrics-tracker-development').read

    json = JSON.parse(response)

    puts "****************** SERVER ERROR: " << json['message'] if json['error'] == 429

    # Add each item as a WP Post
    json['data']['children'].each do |item|

      post_title  = item['data']['title'].gsub("'", "") || 'No Title'
      post_date   = Time.at(item['data']['created_utc']).to_datetime.to_s
      content_url = item['data']['url'].strip

      # Create a post
      post_id = `#{@wpcli} post create \
        --post_title='#{post_title}' \
        --post_status=publish \
        --post_date='#{post_date}' \
        --post_content='#{content_url}' \
        --porcelain`.strip.to_i

      puts "Success: Created a post with ID #{post_id}, #{content_url}" if post_id > 0

      # Add the post meta URL
      system "#{@wpcli} post meta add #{post_id} socialcount_url_data '#{content_url}'" if post_id > 0

    end

  end
end

desc 'Seeds the WP database with some posts and social counts'
task :seed do
  Rake::Task["seed:reddit"].invoke
end
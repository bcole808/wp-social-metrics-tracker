namespace :seed do

  task :reddit => :requires_wpcli do

    # Get data from Reddit
    reddit = JSON.parse(`curl -s -k https://www.reddit.com/rising.json`)

    # Add each item as a WP Post
    reddit['data']['children'].each do |item|

      post_title  = item['data']['title'].gsub("'", "") || 'No Title'
      post_date   = Time.at(item['data']['created']).to_datetime.to_s
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
module.exports = function(grunt) {

	// 1. All configuration goes here
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		sass: {
		    dist: {
		        options: {
		            style: 'compressed'
		        },
		        files: {
		            'src/css/social-metrics-tracker.min.css': 'src/css/social-metrics-tracker.scss'
		        }
		    }
		},

		uglify: {
		    build: {
		        src:  'src/js/social-metrics-tracker.js',
		        dest: 'src/js/social-metrics-tracker.min.js'
		    }
		},

		phpunit: {
		    classes: {
		        dir: ''
		    },
		    options: {
		        bin: 'phpunit',
		        bootstrap: 'tests/bootstrap.php',
		        colors: true,
		        failOnFailures: true, // Allow grunt to continue watching on failure
		        // coverageClover: 'build/logs/clover.xml',
		        excludeGroup: 'external-http'
		    }
		},

		// Watch tasks
		watch: {
			options: {
			    livereload: true,
			},
		    scripts: {
		        files: ['src/js/social-metrics-tracker.js'],
		        tasks: ['uglify'],
		        options: {
		            spawn: false,
		        },
		    },
		    css: {
		        files: ['src/css/*.scss'],
		        tasks: ['sass'],
		        options: {
		            spawn: false,
		        }
		    },
		    php: {
		    	files: ['src/**/*.php', 'tests/**/*.php'],
		    	tasks: ['clear', 'phpunit'],
		    	options : {
		    		spawn: false,
		    	}
		    },
		    templates: {
		    	files: ['src/**/*.handlebars']
		    }
		}

	});

	// 3. Where we tell Grunt we plan to use this plug-in.
	grunt.loadNpmTasks('grunt-clear');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-phpunit');

	// 4. Where we tell Grunt what to do when we type "grunt" into the terminal.
	grunt.registerTask('default', ['sass', 'uglify', 'phpunit']);

};

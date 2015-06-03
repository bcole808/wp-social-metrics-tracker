var path = require('path');

module.exports = function(grunt) {

	// Configuration
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

		php: {
			test: {
				options: {
					hostname: 'localhost',
					port: 8000,
					base: '/tmp/wordpress', // Project root 
					router: path.resolve() + '/router.php',
					keepalive: false,
					open: false
				}
			},
			dev: {
				options: {
					hostname: 'localhost',
					port: 8000,
					base: '/tmp/wordpress', // Project root 
					router: path.resolve() + '/router.php',
					keepalive: true,
					open: true
				}
			}
		},

		'start-selenium-server': {
			dev: {
				options: {
					downloadUrl: 'https://selenium-release.storage.googleapis.com/2.42/selenium-server-standalone-2.42.2.jar',
					downloadLocation: '/tmp',
					serverOptions: {},
					systemProperties: {}
				}
			}
		},
		'stop-selenium-server': {
			dev: {

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

	// Load plugins
	grunt.loadNpmTasks('grunt-clear');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-phpunit');
	grunt.loadNpmTasks('grunt-selenium-server');
	grunt.loadNpmTasks('grunt-php');

	// Task:
	grunt.registerTask('default', ['sass', 'uglify', 'phpunit']);

	// Task:
	grunt.registerTask('test', 'run selenium server and phpunit', function(){
		grunt.task.run(['php:test', 'start-selenium-server:dev', 'phpunit', 'stop-selenium-server:dev']);
	});

	// Task:
	grunt.registerTask('serve', ['php:dev']);



	// Cleanup Tasks
	// Kill selenium in case the grunt task fails before reaching 'stop-selenium-server'
	var seleniumChildProcesses = {};
	
	grunt.event.on('selenium.start', function(target, process){
		grunt.log.ok('Saw process for target: ' +  target);
		seleniumChildProcesses[target] = process;
	});

	grunt.util.hooker.hook(grunt.fail, function(){
		// Clean up selenium if we left it running after a failure. 
		grunt.log.writeln('Attempting to clean up running selenium server.');
		for(var target in seleniumChildProcesses) {
			grunt.log.ok('Killing selenium target: ' + target);
			try {
				seleniumChildProcesses[target].kill('SIGTERM');
			}
			catch(e) {
				grunt.log.warn('Unable to stop selenium target: ' + target);
			}
		}
	});

};

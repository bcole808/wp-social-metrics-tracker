module.exports = function(grunt) {

	// 1. All configuration goes here
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		sass: {
		},

		uglify: {
		},

		phpunit: {
		},

		// Watch tasks
		watch: {
			options: {
			},
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

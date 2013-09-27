var client = require('./client').client;
var expect = require('chai').expect;

// Perform tests

describe('Test example.com', function() {
	before(function(done) {
		client.init().url('http://example.com', done);
	});

	describe('Check homepage', function() {
		
		it('should see the correct title', function(done) {
			client.getTitle(function(title) {
				expect(title).to.have.string('Example Domain');
				done();
			});
		});

		it('should see the body', function(done) {
			client.getText('p', function(p) {
				expect(p).to.have.string('for illustrative examples in documents.');
				done();
			});
		});
		
	});

	after(function(done) {
		client.end();
		done();
	});
});

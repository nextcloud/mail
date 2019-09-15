const sieveSyntaxTree = {
	"require": {},
	"stop": {},
	"if": {},
}

export const ParseSieveError = function(message) {
	this.message = message
}

export const parseSieveScript = (raw) => {
	throw new ParseSieveError("testing")
}
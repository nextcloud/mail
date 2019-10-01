export const ParseSieveError = function(message) {
	this.message = message
}

export const matchTypeBlueprint = {
	"is": {
		"name": t("mail", "is"),
	},
	"contains": {
		"name": t("mail", "contains"),
	},
	"matches": {
		"reqs": ["test2"],
		"name": t("mail", "matches"),
	},
}

export const sieveTestsBlueprint = {
	"subject": {
		"name": t("mail", "Subject"),
		"matchTypes": ["is", "contains", "matches"],
		"opts_default": {
			"negate": false,
			"matchType": "is",
			"value": "",
		},
		"make": function(test) {
			let reqs = new Set([])
			if (matchTypeBlueprint[test.opts.matchType].reqs !== undefined) {
				for (let req of matchTypeBlueprint[test.opts.matchType].reqs) {
					reqs.add(req)
				}
			}
			const negate = test.opts.negate ? "not " : ""
			let script = `${negate} header :${test.opts.matchType} \"subject\" \"${test.opts.value}\"`
			return {reqs, script}
		},
	},
	"from": {
		"name": t("mail", "From"),
		"matchTypes": ["is", "contains", "matches"],
		"opts_default": {
			"negate": false,
			"matchType": "is",
			"value": "",
		},
		"make": function(test) {
			let reqs = new Set([])
			if (matchTypeBlueprint[test.opts.matchType].reqs !== undefined) {
				for (let req of matchTypeBlueprint[test.opts.matchType].reqs) {
					reqs.add(req)
				}
			}
			const negate = test.opts.negate ? "not " : ""
			let script = `${negate} header :${test.opts.matchType} \"from\" \"${test.opts.value}\"`
			return {reqs, script}
		},
	},
	"to": {
		"name": t("mail", "To"),
		"matchTypes": ["is", "contains", "matches"],
		"req": "test",
		"opts_default": {
			"negate": false,
			"matchType": "is",
			"value": "",
		},
		"make": function(test) {
			let reqs = new Set([])
			if (matchTypeBlueprint[test.opts.matchType].reqs !== undefined) {
				for (let req of matchTypeBlueprint[test.opts.matchType].reqs) {
					reqs.add(req)
				}
			}
			const negate = test.opts.negate ? "not " : ""
			let script = `${negate} header :${test.opts.matchType} \"to\" \"${test.opts.value}\"`
			return {reqs, script}
		},
	},
}

export const sieveActionsBlueprint = {
	"move": {
		"name": t("mail", "Move mail into"),
		"opts_default": {
			"value": "SU5CT1g=",
		},
		"make": function(action) {
			let reqs = ["fileinto"]
			let folder = atob(action.opts.value)
			let script = `fileinto ${folder}`
			return {reqs, script}
		},
	},
	"copy": {
		"name": t("mail", "Copy mail into"),
		"opts_default": {
			"value": "SU5CT1g=",
		},
		"make": function(action) {
			let reqs = ["fileinto", "copy"]
			let folder = atob(action.opts.value)
			let script = `fileinto :copy ${folder}`
			return {reqs, script}
		},
	},
}

export const makeSieveScript = function(filters){
	if (filters === undefined) {
		return ""
	} else {
		let raw = ""
		let reqSet = new Set()
		for (const filter of filters) {
			let tests = []
			for (const test of filter.tests.list){
				const {reqs, script} = sieveTestsBlueprint[test.type].make(test)
				tests.push(script)
				for (const req of reqs) {
					reqSet.add(req)
				}
			}
			let actions = []
			for (const action of filter.actions) {
				const {reqs, script} = sieveActionsBlueprint[action.type].make(action)
				actions.push(script)
				for (const req of reqs) {
					reqSet.add(req)
				}
			}

			tests = tests.join(", ")
			actions = actions.join("\n\t")

			raw += 
`
# rule:${filter.name}
if ${filter.tests.type}(${tests})
{
	${actions}
}
`
		}
		const reqs = Array.from(reqSet).join("\",\"")
		const out = 
`require [\"${reqs}\"]
${raw}`
		return out

	}
}

export class sieveTest {
	constructor(test) {
		// separate test type from arguments
		const testRegex = /\s*(\S+)\s+([\S\s]*)/gm
		const testMatch = testRegex.exec(test)
		if (testMatch === undefined) {
			throw new ParseSieveError("test '"+test+"' couldn't be interpreted")
		} else {
			switch (testMatch[1]) {
				case "header":
					const headerRegex = /:(\S+)\s+\"(\S+)\"\s+\"([^"\\]*(?:\\.[^"\\]*)*)\"/gm
					const headerMatch = headerRegex.exec(testMatch[1])
					if (headerMatch === undefined) {
						throw new ParseSieveError("test header arguments '"+testMatch[1]+"' couldn't be interpreted")
					} else {
						headerName = headerMatch[2].toLowerCase()
						switch (headerName) {
							case "subject":
								break

						}
					}
					break
				default:
					throw new ParseSieveError("test type '"+testMatch[1]+"' is not supported")
					break
			}
		}
	}
}
export class sieveAction {
	constructor(action) {
		this.raw = action
	}
}
/*
Supported: if
Missing: else if, else
*/
export class sieveCommand {
	constructor(obj) {
		switch (obj.type) {
			case "if":
				this.name = obj.name
				this.test = new sieveTest(obj.arguments)
				this.actions = new sieveAction(obj.actions)
				break
			default:
				throw new ParseSieveError("command '"+obj.type+"' unknown")
				break
		}
	}
}

export const parseSieveScript = (raw) => {
	if (raw == "") {
		return {"require": [], "filters": []}
	} else {
		let requireList = []
		let commandList = []
		// throw away comments
		const commentRegex = /#(?!\s+rule:).*|\/\*.*\*\/|\/\*(.*\n)+\*\//gm
		raw = raw.replace(commentRegex,"")
		// get require list
		const requireRegex = /require\s*(\[[^{}]*?\]);\s*/gm
		requireList = JSON.parse(requireRegex.exec(raw)[1])
		raw = raw.replace(requireRegex,"")
		// get commands/filters
		const commandRegex = /(# rule:\[(\w+)\]\s*)?(\w+)\s*([^;]*?)\s*\{\s*([^\}]*?)\s*\}\s*/gm
		let match = commandRegex.exec(raw)
		while (match !== null) {
			commandList.push(new sieveCommand({
				"name": match[2],
				"type": match[3],
				"arguments": match[4],
				"actions": match[5],
			}))
			match = commandRegex.exec(raw)
		}
		raw = raw.replace(commandRegex, "")
		// if anything left parse error
		if (raw.match(/\S/gm) !== null) {
			throw new ParseSieveError("unknown script part: '"+raw+"'")
		} else {
			return {"req": requireList, "filters": commandList}
		}
	}
}

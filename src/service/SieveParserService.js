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
		"name": t("mail", "matches"),
	},
}

export const sieveTestsBlueprint = {
	"subject": {
		"name": t("mail", "Subject"),
		"matchTypes": ["is", "contains", "matches"],
		"opts": {
			"negate": false,
			"matchType": "is",
			"value": "",
		}
	},
	"from": {
		"name": t("mail", "From"),
		"matchTypes": ["is", "contains", "matches"],
		"opts": {
			"negate": false,
			"matchType": "is",
			"value": "",
		}
	},
	"to": {
		"name": t("mail", "To"),
		"matchTypes": ["is", "contains", "matches"],
		"opts": {
			"negate": false,
			"matchType": "is",
			"value": "",
		}
	},
}
export const sieveActionsBlueprint = {
	"move": {
		"name": t("mail", "Move mail into"),
		"opts": {
			"value": "",
		}
	},
	"copy": {
		"name": t("mail", "Copy mail into"),
		"opts": {
			"value": "",
		}
	},
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
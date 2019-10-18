/**
 * @copyright Copyright (c) 2019, Merlin Mittelbach <merlin.mittelbach@memit.de>
 *
 * @author 2019, Merlin Mittelbach <merlin.mittelbach@memit.de>
 * @author Pierre Gordon <pierregordon@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
import {generateUrl} from 'nextcloud-router'
import HttpClient from 'nextcloud-axios'

const generateScriptsUrl = (accountId, path = null) => {
	const url = `/apps/mail/api/accounts/{id}/scripts${path ? `/${path}` : ''}`

	return generateUrl(url, {id: accountId})
}

export const getScripts = accountId => {
	const url = generateScriptsUrl(accountId)

	return HttpClient.get(url).then(resp => resp.data.scripts)
}

export const getScript = (accountId, scriptName) => {
	const url = generateScriptsUrl(accountId, scriptName)

	return HttpClient.get(url).then(resp => resp.data.script)
}

export const setActiveScript = (accountId, scriptName) => {
	const url = generateScriptsUrl(accountId, 'active')

	return HttpClient.post(url, {scriptName}).then(resp => resp.data.isActive)
}

export const saveCustomScript = (accountId, script) => {
	const url = generateScriptsUrl(accountId)
	return HttpClient.post(url, {script}).then(resp => resp.data)
}

export const ParseSieveError = function(message) {
	this.message = message
}

export const escapeSieve = function(string) {
	return string
				// if already escaped unescape it
				.replace(/\\\\/g, "\\")
				.replace(/\\"/g, "\"")
				// then escape
				.replace(/\\/g, "\\\\")
				.replace(/"/g, "\\\"")
}

/*
register matchtype here
key: [string] sieve matchtype value
props:
	name: [string] matchtype name (required)
	req: [string] requirement to be imported to sieve script (not required)
*/
export const matchTypeBlueprint = {
	":is": {
		"name": t("mail", "is"),
	},
	":contains": {
		"name": t("mail", "contains"),
	},
	":matches": {
		"name": t("mail", "matches"),
	},
	":regex": {
		"req": "regex",
		"name": t("mail", "matches regular expression"),
	},
}

/*
register test here
key: [string] sieve test id
props:
	name: [string] test name
	req: [string] requirement to be imported to sieve script
	matchTypes: [array of matchTypes] all possible matchTypes to this test
	opts_default: [object] default test options
	make: method to create sieve script
		input:	test object (name, opts)
		output:	req		= array of requirements
				script	= rendered sieve test string
	parse: method to parse sieve script
		input:	test array e.g.: 'not header ":is" "to" "test"'' -> ["not","header",":is","to","test"]
		output:	if test array is recognised: new test object
				else: undefined
*/
export const sieveTestsBlueprint = {
	"subject": {
		"name": t("mail", "Subject"),
		"matchTypes": [":is", ":contains", ":matches", ":regex"],
		"opts_default": {
			"negate": false,
			"matchType": ":is",
			"value": "",
		},
		"make": function(test) {
			let reqs = new Set([])
			if (matchTypeBlueprint[test.opts.matchType].req !== undefined) {
				reqs.add(req)
			}
			const negate = test.opts.negate ? "not " : ""
			const escapedValue = escapeSieve(test.opts.value)
			let script = `${negate}header ${test.opts.matchType} \"subject\" \"${escapedValue}\"`
			return {reqs, script}
		},
		"parse": function(test) {
			let out = {
				"type": "subject",
				"opts": {
					"negate": false,
					"matchType": ":is",
					"value": "",
				}
			}
			if (test[0].toLowerCase() === "not") {
				out.opts.negate = true
				test.shift()
			}
			if (test[0].toLowerCase() === "header" && test[2].toLowerCase() === "subject") {
				if (this.matchTypes.indexOf(test[1]) > -1) {
					out.opts.matchType = test[1]
					out.opts.value = test[3]
					return out
				} else {
					throw new ParseSieveError(`matchtype not supported: '${test[1]}'`)
				}
			} else {
				return undefined
			}
		},
	},
	"from": {
		"name": t("mail", "From"),
		"matchTypes": [":is", ":contains", ":matches", ":regex"],
		"opts_default": {
			"negate": false,
			"matchType": ":is",
			"value": "",
		},
		"make": function(test) {
			let reqs = new Set([])
			if (matchTypeBlueprint[test.opts.matchType].req !== undefined) {
				reqs.add(matchTypeBlueprint[test.opts.matchType].req)
			}
			const negate = test.opts.negate ? "not " : ""
			const escapedValue = escapeSieve(test.opts.value)
			let script = `${negate}header ${test.opts.matchType} \"from\" \"${escapedValue}\"`
			return {reqs, script}
		},
		"parse": function(test) {
			let out = {
				"type": "from",
				"opts": {
					"negate": false,
					"matchType": ":is",
					"value": "",
				}
			}
			if (test[0].toLowerCase() === "not") {
				out.opts.negate = true
				test.shift()
			}
			if (test[0].toLowerCase() === "header" && test[2].toLowerCase() === "from") {
				if (this.matchTypes.indexOf(test[1]) > -1) {
					out.opts.matchType = test[1]
					out.opts.value = test[3]
					return out
				} else {
					throw new ParseSieveError(`matchtype not supported: '${test[1]}'`)
				}
			} else {
				return undefined
			}
		},
	},
	"to": {
		"name": t("mail", "To"),
		"matchTypes": [":is", ":contains", ":matches", ":regex"],
		"opts_default": {
			"negate": false,
			"matchType": ":is",
			"value": "",
		},
		"make": function(test) {
			let reqs = new Set([])
			if (matchTypeBlueprint[test.opts.matchType].req !== undefined) {
				reqs.add(matchTypeBlueprint[test.opts.matchType].req)
			}
			const negate = test.opts.negate ? "not " : ""
			const escapedValue = escapeSieve(test.opts.value)
			let script = `${negate}header ${test.opts.matchType} \"to\" \"${escapedValue}\"`
			return {reqs, script}
		},
		"parse": function(test) {
			let out = {
				"type": "to",
				"opts": {
					"negate": false,
					"matchType": ":is",
					"value": "",
				}
			}
			if (test[0].toLowerCase() === "not") {
				out.opts.negate = true
				test.shift()
			}
			if (test[0].toLowerCase() === "header" && test[2].toLowerCase() === "to") {
				if (this.matchTypes.indexOf(test[1]) > -1) {
					out.opts.matchType = test[1]
					out.opts.value = test[3]
					return out
				} else {
					throw new ParseSieveError(`matchtype not supported: '${test[1]}'`)
				}
			} else {
				return undefined
			}
		},
	},
	"exists": {
		"name": t("mail", "Header exists"),
		"opts_default": {
			"negate": false,
			"value": "",
		},
		"make": function(test) {
			let reqs = new Set([])
			const negate = test.opts.negate ? "not " : ""
			const escapedValue = escapeSieve(test.opts.value)
			let script = `${negate}exists \"${escapedValue}\"`
			return {reqs, script}
		},
		"parse": function(test) {
			let out = {
				"type": "exists",
				"opts": {
					"negate": false,
					"value": "",
				}
			}
			if (test[0].toLowerCase() === "not") {
				out.opts.negate = true
				test.shift()
			}
			if (test[0].toLowerCase() === "exists") {
				out.opts.value = test[1]
				return out
			} else {
				return undefined
			}
		},
	},
	"content": {
		"name": t("mail", "Content"),
		"matchTypes": [":is", ":contains", ":matches", ":regex"],
		"req": "body",
		"opts_default": {
			"negate": false,
			"matchType": ":is",
			"value": "",
		},
		"make": function(test) {
			let reqs = new Set([this.req])
			if (matchTypeBlueprint[test.opts.matchType].req !== undefined) {
				reqs.add(matchTypeBlueprint[test.opts.matchType].req)
			}
			const negate = test.opts.negate ? "not " : ""
			const escapedValue = escapeSieve(test.opts.value)
			let script = `${negate}body :text ${test.opts.matchType} \"${escapedValue}\"`
			return {reqs, script}
		},
		"parse": function(test) {
			let out = {
				"type": "content",
				"opts": {
					"negate": false,
					"matchType": ":is",
					"value": "",
				}
			}
			if (test[0].toLowerCase() === "not") {
				out.opts.negate = true
				test.shift()
			}
			if (test[0].toLowerCase() === "body" && test.indexOf(":text") > -1) {
				const matchTypeIndex = test.indexOf(":text") === 1 ? 2 : 1
				if (this.matchTypes.indexOf(test[matchTypeIndex]) > -1) {
					out.opts.matchType = test[matchTypeIndex]
					out.opts.value = test[3]
					return out
				} else {
					throw new ParseSieveError(`matchtype not supported: '${test[1]}'`)
				}
			} else {
				return undefined
			}
		},
	},
}
/*
register action here
key: [string] sieve action id
props:
	name: [string] action name
	req: [string] requirement to be imported to sieve script
	opts_default: [object] default action options
	make: method to create sieve script
		input:	action object (name, opts)
		output:	req		= array of requirements
				script	= rendered sieve action string
	parse: method to parse sieve script
		input:	action array e.g.: 'fileinto :copy "INBOX"' -> ["fileinto", ":copy", "INBOX"]
		output:	if test array is recognised: new test object
				else: undefined
*/
export const sieveActionsBlueprint = {
	"move": {
		"name": t("mail", "Move mail into"),
		"opts_default": {
			"value": "SU5CT1g=",
		},
		"make": function(action) {
			let reqs = ["fileinto"]
			let folder = atob(action.opts.value)
			let script = `fileinto ${folder};`
			return {reqs, script}
		},
		"parse": function(action) {
			let out = {
				"type": "move",
				"opts": {
					"value": btoa(action[1]),
				}
			}
			if (action[0].toLowerCase() === "fileinto" && action.length === 2) {
				return out
			} else {
				return undefined
			}
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
			let script = `fileinto :copy ${folder};`
			return {reqs, script}
		},
		"parse": function(action) {
			let out = {
				"type": "copy",
				"opts": {
					"value": btoa(action[2]),
				}
			}
			if (action[0].toLowerCase() === "fileinto" && action[1] === ":copy" && action.length === 3) {
				return out
			} else {
				return undefined
			}
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
# rule:[${filter.name}]
if ${filter.tests.type}(${tests})
{
	${actions}
}
`
		}
		const reqs = Array.from(reqSet).join("\",\"")
		if (reqs === "") {
			return `${raw}`
		} else {
			return `require [\"${reqs}\"];
${raw}`
		}
	}
}

export const parseSieveScript = (raw) => {
	if (raw === "" || raw === undefined) {
		return []
	} else {
		let filters = []
		let match

		// throw away require list
		const requireRegex = /require[\s\S]*?;\s*/gm
		raw = raw.replace(requireRegex,"")
		
		let strings = {}
		let counter = 0
		// separate multiline strings
		const multilineRegex = /text:\s*?(.[\s\S]*?)^\.\n/gm
		match = multilineRegex.exec(raw)
		while (match !== null) {
			strings[`__${counter}__`] = match[1]
			raw = raw.replace(match[0], `__${counter}__`)
			counter += 1
			match = multilineRegex.exec(raw)
		}
		// separate one line strings
		const matches = raw.match(/"[^"\\]*(?:\\.[^"\\]*)*"/gm)
		if (matches !== null) {
			for (const match of matches) {
				raw = raw.replace(match, `__${counter}__`)
				strings[`__${counter}__`] = match.slice(1,-1)
				counter += 1
			}
		}
		console.log
		// unescape strings
		for (const key of Object.keys(strings)) {
			strings[key] = strings[key]
										.replace(/\\"/g, "\"")
										.replace(/\\\\/g, "\\")
		}

		// throw away comments
		const commentRegex = /#(?!\s+rule:).*|\/\*.*\*\/|\/\*(.*\n)+\*\//gm
		raw = raw.replace(commentRegex,"")
		
		// get commands/filters
		const commandRegex = /(?:# rule:\[(\w+)\]\s*)?^(\w+)\s*([^;]*?)\s*\{\s*([^\}]*?)\s*\}\s*/gm
		match = commandRegex.exec(raw)
		let newFilterID = 0
		while (match !== null) {
			const [ , name, command_type, testsArray, actionsRaw] = match
			let filter = {
				"id": newFilterID,
				"name": name !== undefined ? name : "Filter_"+newFilterID,
				"tests": {
					"type": "allof",
					"list": [],
				},
				"actions": [], /*[{
					"id": 0,
					"type": "move",
					"opts": JSON.parse(JSON.stringify(sieveActionsBlueprint["move"].opts_default))
				}],*/
			}

			if (command_type === "if") {
				
				// parse tests
				const testsArrayRegex = /(allof|anyof)?\s*\(?\s*([\s\S]*.)\s*\)/gm
				const testMatch = testsArrayRegex.exec(testsArray)
				let tests
				if (testMatch === null) {
					tests = [ testsArray ]
				} else {
					filter.tests.type = testMatch[1]
					tests = testMatch[2].split(/\s*,\s*/)
				}
				let newTestID = 0
				for (let test of tests) {
					test = test.split(/\s+/)
					let newTest
					for (const testType in sieveTestsBlueprint) {
						if (newTest === undefined){
							newTest = sieveTestsBlueprint[testType].parse(test.map(x => strings[x] === undefined ? x : strings[x]))
						}
					}
					if (newTest === undefined) {
						let error = test.map(x => strings[x] === undefined ? x : strings[x])
						error = error.join(" ")
						throw new ParseSieveError(`test couldn't be interpreted: '${error}'`)
					} else {
						newTest["id"] = newTestID
						newTestID += 1
					}
					filter.tests.list.push(newTest)
				}

				// parse actions
				const actions = actionsRaw.split(/;\s*/).filter(x => x !== "")
				let newActionID = 0
				for (let action of actions) {
					action = action.split(/\s+/)
					let newAction
					for (const actionType in sieveActionsBlueprint) {
						if (newAction === undefined){
							newAction = sieveActionsBlueprint[actionType].parse(action.map(x => strings[x] === undefined ? x : strings[x]))
						}
					}
					if (newAction === undefined) {
						let error = action.map(x => strings[x] === undefined ? x : strings[x])
						error = error.join(" ")
						throw new ParseSieveError(`action couldn't be interpreted: '${error}'`)
					} else {
						newAction["id"] = newActionID
						newActionID += 1
					}
					filter.actions.push(newAction)
				}
			} else {
				throw new ParseSieveError(`command type not supported: '${type}'`)
			}

			match = commandRegex.exec(raw)
			newFilterID += 1
			filters.push(filter)
		}
		raw = raw.replace(commandRegex, "")
		// if anything left parse error
		if (raw.match(/\S/gm) !== null) {
			let error = raw.replace(/\n/gm, " ")
			throw new ParseSieveError(`unknown script part: '${error}'`)
		} else {
			return filters
		}
	}
}
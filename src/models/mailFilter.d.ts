export declare enum MailFilterOperator {
    All = 'allof',
    Any = 'anyof'
}
export declare enum MailFilterTestField {
    Subject = 'subject',
    From = 'from',
    To = 'to'
}
export declare enum MailFilterTestOperator {
    Is = 'is',
    Contains = 'contains',
    Matches = 'matches'
}
export declare class MailFilterTest {

	id: number
	field: MailFilterTestField
	operator: MailFilterTestOperator
	values: string[]
	constructor();
	hasValues(): boolean;

}
interface MailFilterAction {
    id: number;
    type: string;
}
export declare class MailFilterActionAddflag implements MailFilterAction {

	id: number
	type: string
	flag: string
	constructor();

}
export declare class MailFilterActionMailbox implements MailFilterAction {

	id: number
	type: string
	mailbox: string
	constructor();

}
export declare class MailFilterActionStop implements MailFilterAction {

	id: number
	type: string
	constructor();

}
export declare class MailFilter {

	id: number
	name: string
	enable: boolean
	operator: MailFilterOperator
	tests: MailFilterTest[]
	actions: MailFilterAction[]
	priority: number
	constructor();

}
export {}

export type Constructor = new (...args: any[]) => any;
export type Func = (...args: any[]) => any;
export declare const instanceOfAny: (object: any, constructors: Constructor[]) => boolean;

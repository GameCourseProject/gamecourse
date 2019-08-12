/* parser generated by jison 0.4.18 */
/*
  Returns a Parser object of the following structure:

  Parser: {
    yy: {}
  }

  Parser.prototype: {
    yy: {},
    trace: function(),
    symbols_: {associative list: name ==> number},
    terminals_: {associative list: number ==> name},
    productions_: [...],
    performAction: function anonymous(yytext, yyleng, yylineno, yy, yystate, $$, _$),
    table: [...],
    defaultActions: {...},
    parseError: function(str, hash),
    parse: function(input),

    lexer: {
        EOF: 1,
        parseError: function(str, hash),
        setInput: function(input),
        input: function(),
        unput: function(str),
        more: function(),
        less: function(n),
        pastInput: function(),
        upcomingInput: function(),
        showPosition: function(),
        test_match: function(regex_match_array, rule_index),
        next: function(),
        lex: function(),
        begin: function(condition),
        popState: function(),
        _currentRules: function(),
        topState: function(),
        pushState: function(condition),

        options: {
            ranges: boolean           (optional: true ==> token location info will include a .range[] member)
            flex: boolean             (optional: true ==> flex-like lexing behaviour where the rules are tested exhaustively to find the longest match)
            backtrack_lexer: boolean  (optional: true ==> lexer regexes are tested in order and for each matching regex the action code is invoked; the lexer terminates the scan when a token is returned by the action code)
        },

        performAction: function(yy, yy_, $avoiding_name_collisions, YY_START),
        rules: [...],
        conditions: {associative list: name ==> set},
    }
  }


  token location info (@$, _$, etc.): {
    first_line: n,
    last_line: n,
    first_column: n,
    last_column: n,
    range: [start_number, end_number]       (where the numbers are indexes into the input string, regular zero-based)
  }


  the parseError function receives a 'hash' object with these members for lexer and parser errors: {
    text:        (matched text)
    token:       (the produced terminal token, if any)
    line:        (yylineno)
  }
  while parser (grammar) errors will also provide these members, i.e. parser errors deliver a superset of attributes: {
    loc:         (yylloc)
    expected:    (string describing the set of expected tokens)
    recoverable: (boolean: TRUE when the parser has a error recovery rule available for this particular error)
  }
*/
var SmartboardsExpression = (function(){
var o=function(k,v,o,l){for(o=o||{},l=k.length;l--;o[k[l]]=v);return o},$V0=[1,4],$V1=[1,5],$V2=[1,6],$V3=[1,15],$V4=[1,11],$V5=[1,9],$V6=[1,10],$V7=[1,12],$V8=[1,14],$V9=[1,16],$Va=[1,17],$Vb=[1,18],$Vc=[1,9,11,12],$Vd=[1,20],$Ve=[1,21],$Vf=[1,22],$Vg=[1,23],$Vh=[1,24],$Vi=[1,25],$Vj=[1,26],$Vk=[1,27],$Vl=[1,28],$Vm=[1,29],$Vn=[1,30],$Vo=[1,31],$Vp=[8,10,13,14,15,16,17,18,19,20,22,23,24,25,29],$Vq=[8,10,13,14,18,19,20,22,23,24,25,29],$Vr=[8,10,18,19,23,24,25,29],$Vs=[8,10,18,19,20,22,23,24,25,29],$Vt=[8,10,29],$Vu=[8,10,13,14,15,16,17,18,19,20,22,23,24,25,29,35];
var parser = {trace: function trace() { },
yy: {},
symbols_: {"error":2,"start":3,"block":4,"stmt":5,"arglist":6,"exp":7,",":8,"EXPR_START":9,"EXPR_END":10,"TEXT":11,"PARAM":12,"+":13,"-":14,"*":15,"/":16,"%":17,"EQUALS":18,"<":19,">":20,"=":21,"IN":22,"&":23,"|":24,"^":25,"~":26,"!":27,"(":28,")":29,"function":30,"totalpath":31,"STRING":32,"NUMBER":33,"PATH":34,"PATH_SEPARATOR":35,"$accept":0,"$end":1},
terminals_: {2:"error",8:",",9:"EXPR_START",10:"EXPR_END",11:"TEXT",12:"PARAM",13:"+",14:"-",15:"*",16:"/",17:"%",18:"EQUALS",19:"<",20:">",21:"=",22:"IN",23:"&",24:"|",25:"^",26:"~",27:"!",28:"(",29:")",31:"totalpath",32:"STRING",33:"NUMBER",34:"PATH",35:"PATH_SEPARATOR"},
productions_: [0,[3,0],[3,1],[4,1],[4,2],[6,1],[6,3],[5,3],[5,1],[5,1],[7,3],[7,3],[7,3],[7,3],[7,3],[7,3],[7,3],[7,3],[7,4],[7,4],[7,3],[7,4],[7,4],[7,3],[7,3],[7,3],[7,2],[7,2],[7,2],[7,3],[7,1],[7,1],[7,1],[7,1],[7,1],[30,3],[30,6],[30,3],[30,6],[30,3],[30,6]],
performAction: function anonymous(yytext, yyleng, yylineno, yy, yystate /* action[1] */, $$ /* vstack */, _$ /* lstack */) {
/* this == yyval */

var $0 = $$.length - 1;
switch (yystate) {
case 1:
   //js
            /*php return new ValueNode(''); */
        
break;
case 2:
   //js
            /*php return $$[$0].yytext; */
        
break;
case 3:
/*php
            this.$ = $$[$0].yytext;
        */
break;
case 4:
/*php
            this.$ = new StatementSequence($$[$0-1].yytext, $$[$0].yytext);
        */
break;
case 5:
/*php
            this.$ = new ArgumentSequence($$[$0].yytext);
        */
break;
case 6:
/*php
            this.$ = new ArgumentSequence($$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 7: case 29:
/*php
            this.$ = $$[$0-1].yytext;
        */
break;
case 8:
   //js
            /*php
            this.$ = new ValueNode($$[$0].yytext);
        */
break;
case 9: case 32:
/*php
            this.$ = new ParameterNode(substr($$[$0].yytext, 1));
        */
break;
case 10:
/*php
            this.$ = new GenericBinaryOp('+', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 11:
/*php
            this.$ = new GenericBinaryOp('-', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 12:
/*php
            this.$ = new GenericBinaryOp('*', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 13:
/*php
            this.$ =  new GenericBinaryOp('/', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 14:
/*php
            this.$ =  new GenericBinaryOp('%', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 15:
/*php
            this.$ = new GenericBinaryOp('==', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 16:
/*php
            this.$ = new GenericBinaryOp('<', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 17:
/*php
            this.$ = new GenericBinaryOp('>', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 18:
/*php
            this.$ = new GenericBinaryOp('<=', $$[$0-3].yytext, $$[$0].yytext);
        */
break;
case 19:
/*php
            this.$ = new GenericBinaryOp('>=', $$[$0-3].yytext, $$[$0].yytext);
        */
break;
case 20:
/*php
            this.$ = new GenericBinaryOp('in_array', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 21:
/*php
            this.$ = new GenericBinaryOp('&&', $$[$0-3].yytext, $$[$0].yytext);
        */
break;
case 22:
/*php
            this.$ = new GenericBinaryOp('||', $$[$0-3].yytext, $$[$0].yytext);
        */
break;
case 23:
/*php
            this.$ = new GenericBinaryOp('&', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 24:
/*php
            this.$ = new GenericBinaryOp('|', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 25:
/*php
            this.$ = new GenericBinaryOp('||', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 26:
/*php
            this.$ = new GenericUnaryOp('~', $$[$0].yytext);
        */
break;
case 27:
/*php
            this.$ = new GenericUnaryOp('!', $$[$0].yytext);
        */
break;
case 28:
/*php
            this.$ = new GenericUnaryOp('-', $$[$0].yytext);
        */
break;
case 30: case 31:
   //js
            CodeAssistant.reset();
            /*php this.$ = $$[$0]->text; */
        
break;
case 33:
/*php
            this.$ = new ValueNode($$[$0].yytext);
        */
break;
case 34:
/*php
            this.$ = new ValueNode((int) ($$[$0].yytext));
        */
break;
case 35:
/*php
            this.$ = new FunctionOp($$[$0].yytext, null, $$[$0-2].yytext);
        */
break;
case 36:
/*php
            this.$ = new FunctionOp($$[$0-3].yytext, $$[$0-1].yytext, $$[$0-5].yytext);
        */
break;
case 37:
/*php
            this.$ = new FunctionOp($$[$0].yytext, null, null,new ParameterNode(substr($$[$0-2].yytext, 1)));
        */
break;
case 38:
/*php
            this.$ = new FunctionOp($$[$0-3].yytext, $$[$0-1].yytext, null,new ParameterNode(substr($$[$0-5].yytext, 1)));
        */
break;
case 39:
/*php
            this.$ = new FunctionOp($$[$0].yytext, null, null,$$[$0-2].yytext);
        */
break;
case 40:
/*php
            this.$ = new FunctionOp($$[$0-3].yytext, $$[$0-1].yytext, null,$$[$0-5].yytext);
        */
break;
}
},
table: [{1:[2,1],3:1,4:2,5:3,9:$V0,11:$V1,12:$V2},{1:[3]},{1:[2,2]},{1:[2,3],4:7,5:3,9:$V0,11:$V1,12:$V2},{7:8,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},o($Vc,[2,8]),o($Vc,[2,9]),{1:[2,4]},{10:[1,19],13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,18:$Vi,19:$Vj,20:$Vk,22:$Vl,23:$Vm,24:$Vn,25:$Vo},{7:32,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:33,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:34,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:35,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},o($Vp,[2,30],{35:[1,36]}),o($Vp,[2,31]),o($Vp,[2,32],{35:[1,37]}),o($Vp,[2,33]),o($Vp,[2,34]),{35:[1,38]},o($Vc,[2,7]),{7:39,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:40,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:41,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:42,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:43,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:44,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:45,12:$V3,14:$V4,21:[1,46],26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:47,12:$V3,14:$V4,21:[1,48],26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:49,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:51,12:$V3,14:$V4,23:[1,50],26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:53,12:$V3,14:$V4,24:[1,52],26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{7:54,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},o($Vp,[2,26]),o($Vp,[2,27]),o($Vp,[2,28]),{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,18:$Vi,19:$Vj,20:$Vk,22:$Vl,23:$Vm,24:$Vn,25:$Vo,29:[1,55]},{34:[1,56]},{34:[1,57]},{34:[1,58]},o($Vq,[2,10],{15:$Vf,16:$Vg,17:$Vh}),o($Vq,[2,11],{15:$Vf,16:$Vg,17:$Vh}),o($Vp,[2,12]),o($Vp,[2,13]),o($Vp,[2,14]),o([8,10,18,23,24,25,29],[2,15],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,19:$Vj,20:$Vk,22:$Vl}),o($Vr,[2,16],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,20:$Vk,22:$Vl}),{7:59,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},o($Vs,[2,17],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh}),{7:60,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},o($Vs,[2,20],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh}),{7:61,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},o($Vt,[2,23],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,18:$Vi,19:$Vj,20:$Vk,22:$Vl,23:$Vm,24:$Vn,25:$Vo}),{7:62,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},o($Vt,[2,24],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,18:$Vi,19:$Vj,20:$Vk,22:$Vl,23:$Vm,24:$Vn,25:$Vo}),o($Vt,[2,25],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,18:$Vi,19:$Vj,20:$Vk,22:$Vl,23:$Vm,24:$Vn,25:$Vo}),o($Vp,[2,29]),o($Vu,[2,39],{28:[1,63]}),o($Vu,[2,37],{28:[1,64]}),o($Vu,[2,35],{28:[1,65]}),o($Vr,[2,18],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,20:$Vk,22:$Vl}),o($Vs,[2,19],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh}),o($Vt,[2,21],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,18:$Vi,19:$Vj,20:$Vk,22:$Vl,23:$Vm,24:$Vn,25:$Vo}),o($Vt,[2,22],{13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,18:$Vi,19:$Vj,20:$Vk,22:$Vl,23:$Vm,24:$Vn,25:$Vo}),{6:66,7:67,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{6:68,7:67,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{6:69,7:67,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},{29:[1,70]},{8:[1,71],13:$Vd,14:$Ve,15:$Vf,16:$Vg,17:$Vh,18:$Vi,19:$Vj,20:$Vk,22:$Vl,23:$Vm,24:$Vn,25:$Vo,29:[2,5]},{29:[1,72]},{29:[1,73]},o($Vu,[2,40]),{6:74,7:67,12:$V3,14:$V4,26:$V5,27:$V6,28:$V7,30:13,31:$V8,32:$V9,33:$Va,34:$Vb},o($Vu,[2,38]),o($Vu,[2,36]),{29:[2,6]}],
defaultActions: {2:[2,2],7:[2,4],74:[2,6]},
parseError: function parseError(str, hash) {
    if (hash.recoverable) {
        this.trace(str);
    } else {
        var error = new Error(str);
        error.hash = hash;
        throw error;
    }
},
parse: function parse(input) {
    var self = this, stack = [0], tstack = [], vstack = [null], lstack = [], table = this.table, yytext = '', yylineno = 0, yyleng = 0, recovering = 0, TERROR = 2, EOF = 1;
    var args = lstack.slice.call(arguments, 1);
    var lexer = Object.create(this.lexer);
    var sharedState = { yy: {} };
    for (var k in this.yy) {
        if (Object.prototype.hasOwnProperty.call(this.yy, k)) {
            sharedState.yy[k] = this.yy[k];
        }
    }
    lexer.setInput(input, sharedState.yy);
    sharedState.yy.lexer = lexer;
    sharedState.yy.parser = this;
    if (typeof lexer.yylloc == 'undefined') {
        lexer.yylloc = {};
    }
    var yyloc = lexer.yylloc;
    lstack.push(yyloc);
    var ranges = lexer.options && lexer.options.ranges;
    if (typeof sharedState.yy.parseError === 'function') {
        this.parseError = sharedState.yy.parseError;
    } else {
        this.parseError = Object.getPrototypeOf(this).parseError;
    }
    function popStack(n) {
        stack.length = stack.length - 2 * n;
        vstack.length = vstack.length - n;
        lstack.length = lstack.length - n;
    }
    _token_stack:
        var lex = function () {
            var token;
            token = lexer.lex() || EOF;
            if (typeof token !== 'number') {
                token = self.symbols_[token] || token;
            }
            return token;
        };
    var symbol, preErrorSymbol, state, action, a, r, yyval = {}, p, len, newState, expected;
    while (true) {
        state = stack[stack.length - 1];
        if (this.defaultActions[state]) {
            action = this.defaultActions[state];
        } else {
            if (symbol === null || typeof symbol == 'undefined') {
                symbol = lex();
            }
            action = table[state] && table[state][symbol];
        }
                    if (typeof action === 'undefined' || !action.length || !action[0]) {
                var errStr = '';
                expected = [];
                for (p in table[state]) {
                    if (this.terminals_[p] && p > TERROR) {
                        expected.push('\'' + this.terminals_[p] + '\'');
                    }
                }
                if (lexer.showPosition) {
                    errStr = 'Parse error on line ' + (yylineno + 1) + ':\n' + lexer.showPosition() + '\nExpecting ' + expected.join(', ') + ', got \'' + (this.terminals_[symbol] || symbol) + '\'';
                } else {
                    errStr = 'Parse error on line ' + (yylineno + 1) + ': Unexpected ' + (symbol == EOF ? 'end of input' : '\'' + (this.terminals_[symbol] || symbol) + '\'');
                }
                this.parseError(errStr, {
                    text: lexer.match,
                    token: this.terminals_[symbol] || symbol,
                    line: lexer.yylineno,
                    loc: yyloc,
                    expected: expected
                });
            }
        if (action[0] instanceof Array && action.length > 1) {
            throw new Error('Parse Error: multiple actions possible at state: ' + state + ', token: ' + symbol);
        }
        switch (action[0]) {
        case 1:
            stack.push(symbol);
            vstack.push(lexer.yytext);
            lstack.push(lexer.yylloc);
            stack.push(action[1]);
            symbol = null;
            if (!preErrorSymbol) {
                yyleng = lexer.yyleng;
                yytext = lexer.yytext;
                yylineno = lexer.yylineno;
                yyloc = lexer.yylloc;
                if (recovering > 0) {
                    recovering--;
                }
            } else {
                symbol = preErrorSymbol;
                preErrorSymbol = null;
            }
            break;
        case 2:
            len = this.productions_[action[1]][1];
            yyval.$ = vstack[vstack.length - len];
            yyval._$ = {
                first_line: lstack[lstack.length - (len || 1)].first_line,
                last_line: lstack[lstack.length - 1].last_line,
                first_column: lstack[lstack.length - (len || 1)].first_column,
                last_column: lstack[lstack.length - 1].last_column
            };
            if (ranges) {
                yyval._$.range = [
                    lstack[lstack.length - (len || 1)].range[0],
                    lstack[lstack.length - 1].range[1]
                ];
            }
            r = this.performAction.apply(yyval, [
                yytext,
                yyleng,
                yylineno,
                sharedState.yy,
                action[1],
                vstack,
                lstack
            ].concat(args));
            if (typeof r !== 'undefined') {
                return r;
            }
            if (len) {
                stack = stack.slice(0, -1 * len * 2);
                vstack = vstack.slice(0, -1 * len);
                lstack = lstack.slice(0, -1 * len);
            }
            stack.push(this.productions_[action[1]][0]);
            vstack.push(yyval.$);
            lstack.push(yyval._$);
            newState = table[stack[stack.length - 2]][stack[stack.length - 1]];
            stack.push(newState);
            break;
        case 3:
            return true;
        }
    }
    return true;
}};
/* generated by jison-lex 0.3.4 */
var lexer = (function(){
var lexer = ({

EOF:1,

parseError:function parseError(str, hash) {
        if (this.yy.parser) {
            this.yy.parser.parseError(str, hash);
        } else {
            throw new Error(str);
        }
    },

// resets the lexer, sets new input
setInput:function (input, yy) {
        this.yy = yy || this.yy || {};
        this._input = input;
        this._more = this._backtrack = this.done = false;
        this.yylineno = this.yyleng = 0;
        this.yytext = this.matched = this.match = '';
        this.conditionStack = ['INITIAL'];
        this.yylloc = {
            first_line: 1,
            first_column: 0,
            last_line: 1,
            last_column: 0
        };
        if (this.options.ranges) {
            this.yylloc.range = [0,0];
        }
        this.offset = 0;
        return this;
    },

// consumes and returns one char from the input
input:function () {
        var ch = this._input[0];
        this.yytext += ch;
        this.yyleng++;
        this.offset++;
        this.match += ch;
        this.matched += ch;
        var lines = ch.match(/(?:\r\n?|\n).*/g);
        if (lines) {
            this.yylineno++;
            this.yylloc.last_line++;
        } else {
            this.yylloc.last_column++;
        }
        if (this.options.ranges) {
            this.yylloc.range[1]++;
        }

        this._input = this._input.slice(1);
        return ch;
    },

// unshifts one char (or a string) into the input
unput:function (ch) {
        var len = ch.length;
        var lines = ch.split(/(?:\r\n?|\n)/g);

        this._input = ch + this._input;
        this.yytext = this.yytext.substr(0, this.yytext.length - len);
        //this.yyleng -= len;
        this.offset -= len;
        var oldLines = this.match.split(/(?:\r\n?|\n)/g);
        this.match = this.match.substr(0, this.match.length - 1);
        this.matched = this.matched.substr(0, this.matched.length - 1);

        if (lines.length - 1) {
            this.yylineno -= lines.length - 1;
        }
        var r = this.yylloc.range;

        this.yylloc = {
            first_line: this.yylloc.first_line,
            last_line: this.yylineno + 1,
            first_column: this.yylloc.first_column,
            last_column: lines ?
                (lines.length === oldLines.length ? this.yylloc.first_column : 0)
                 + oldLines[oldLines.length - lines.length].length - lines[0].length :
              this.yylloc.first_column - len
        };

        if (this.options.ranges) {
            this.yylloc.range = [r[0], r[0] + this.yyleng - len];
        }
        this.yyleng = this.yytext.length;
        return this;
    },

// When called from action, caches matched text and appends it on next action
more:function () {
        this._more = true;
        return this;
    },

// When called from action, signals the lexer that this rule fails to match the input, so the next matching rule (regex) should be tested instead.
reject:function () {
        if (this.options.backtrack_lexer) {
            this._backtrack = true;
        } else {
            return this.parseError('Lexical error on line ' + (this.yylineno + 1) + '. You can only invoke reject() in the lexer when the lexer is of the backtracking persuasion (options.backtrack_lexer = true).\n' + this.showPosition(), {
                text: "",
                token: null,
                line: this.yylineno
            });

        }
        return this;
    },

// retain first n characters of the match
less:function (n) {
        this.unput(this.match.slice(n));
    },

// displays already matched input, i.e. for error messages
pastInput:function () {
        var past = this.matched.substr(0, this.matched.length - this.match.length);
        return (past.length > 20 ? '...':'') + past.substr(-20).replace(/\n/g, "");
    },

// displays upcoming input, i.e. for error messages
upcomingInput:function () {
        var next = this.match;
        if (next.length < 20) {
            next += this._input.substr(0, 20-next.length);
        }
        return (next.substr(0,20) + (next.length > 20 ? '...' : '')).replace(/\n/g, "");
    },

// displays the character position where the lexing error occurred, i.e. for error messages
showPosition:function () {
        var pre = this.pastInput();
        var c = new Array(pre.length + 1).join("-");
        return pre + this.upcomingInput() + "\n" + c + "^";
    },

// test the lexed token: return FALSE when not a match, otherwise return token
test_match:function (match, indexed_rule) {
        var token,
            lines,
            backup;

        if (this.options.backtrack_lexer) {
            // save context
            backup = {
                yylineno: this.yylineno,
                yylloc: {
                    first_line: this.yylloc.first_line,
                    last_line: this.last_line,
                    first_column: this.yylloc.first_column,
                    last_column: this.yylloc.last_column
                },
                yytext: this.yytext,
                match: this.match,
                matches: this.matches,
                matched: this.matched,
                yyleng: this.yyleng,
                offset: this.offset,
                _more: this._more,
                _input: this._input,
                yy: this.yy,
                conditionStack: this.conditionStack.slice(0),
                done: this.done
            };
            if (this.options.ranges) {
                backup.yylloc.range = this.yylloc.range.slice(0);
            }
        }

        lines = match[0].match(/(?:\r\n?|\n).*/g);
        if (lines) {
            this.yylineno += lines.length;
        }
        this.yylloc = {
            first_line: this.yylloc.last_line,
            last_line: this.yylineno + 1,
            first_column: this.yylloc.last_column,
            last_column: lines ?
                         lines[lines.length - 1].length - lines[lines.length - 1].match(/\r?\n?/)[0].length :
                         this.yylloc.last_column + match[0].length
        };
        this.yytext += match[0];
        this.match += match[0];
        this.matches = match;
        this.yyleng = this.yytext.length;
        if (this.options.ranges) {
            this.yylloc.range = [this.offset, this.offset += this.yyleng];
        }
        this._more = false;
        this._backtrack = false;
        this._input = this._input.slice(match[0].length);
        this.matched += match[0];
        token = this.performAction.call(this, this.yy, this, indexed_rule, this.conditionStack[this.conditionStack.length - 1]);
        if (this.done && this._input) {
            this.done = false;
        }
        if (token) {
            return token;
        } else if (this._backtrack) {
            // recover context
            for (var k in backup) {
                this[k] = backup[k];
            }
            return false; // rule action called reject() implying the next rule should be tested instead.
        }
        return false;
    },

// return next match in input
next:function () {
        if (this.done) {
            return this.EOF;
        }
        if (!this._input) {
            this.done = true;
        }

        var token,
            match,
            tempMatch,
            index;
        if (!this._more) {
            this.yytext = '';
            this.match = '';
        }
        var rules = this._currentRules();
        for (var i = 0; i < rules.length; i++) {
            tempMatch = this._input.match(this.rules[rules[i]]);
            if (tempMatch && (!match || tempMatch[0].length > match[0].length)) {
                match = tempMatch;
                index = i;
                if (this.options.backtrack_lexer) {
                    token = this.test_match(tempMatch, rules[i]);
                    if (token !== false) {
                        return token;
                    } else if (this._backtrack) {
                        match = false;
                        continue; // rule action called reject() implying a rule MISmatch.
                    } else {
                        // else: this is a lexer rule which consumes input without producing a token (e.g. whitespace)
                        return false;
                    }
                } else if (!this.options.flex) {
                    break;
                }
            }
        }
        if (match) {
            token = this.test_match(match, rules[index]);
            if (token !== false) {
                return token;
            }
            // else: this is a lexer rule which consumes input without producing a token (e.g. whitespace)
            return false;
        }
        if (this._input === "") {
            return this.EOF;
        } else {
            return this.parseError('Lexical error on line ' + (this.yylineno + 1) + '. Unrecognized text.\n' + this.showPosition(), {
                text: "",
                token: null,
                line: this.yylineno
            });
        }
    },

// return next match that has a token
lex:function lex() {
        var r = this.next();
        if (r) {
            return r;
        } else {
            return this.lex();
        }
    },

// activates a new lexer condition state (pushes the new lexer condition state onto the condition stack)
begin:function begin(condition) {
        this.conditionStack.push(condition);
    },

// pop the previously active lexer condition state off the condition stack
popState:function popState() {
        var n = this.conditionStack.length - 1;
        if (n > 0) {
            return this.conditionStack.pop();
        } else {
            return this.conditionStack[0];
        }
    },

// produce the lexer rule set which is active for the currently active lexer condition state
_currentRules:function _currentRules() {
        if (this.conditionStack.length && this.conditionStack[this.conditionStack.length - 1]) {
            return this.conditions[this.conditionStack[this.conditionStack.length - 1]].rules;
        } else {
            return this.conditions["INITIAL"].rules;
        }
    },

// return the currently active lexer condition state; when an index argument is provided it produces the N-th previous condition state, if available
topState:function topState(n) {
        n = this.conditionStack.length - 1 - Math.abs(n || 0);
        if (n >= 0) {
            return this.conditionStack[n];
        } else {
            return "INITIAL";
        }
    },

// alias for begin(condition)
pushState:function pushState(condition) {
        this.begin(condition);
    },

// return the number of states currently on the stack
stateStackSize:function stateStackSize() {
        return this.conditionStack.length;
    },
options: {},
performAction: function anonymous(yy,yy_,$avoiding_name_collisions,YY_START) {
var YYSTATE=YY_START;
switch($avoiding_name_collisions) {
case 0:   //js
                                this.begin('EXPR');
                                /*php $this->begin('EXPR'); */
                                return 9;
                            
break;
case 1:   //js
                                yy_.yytext = '%';
                                /*php $this->yy->text = '%'; */
                                return 11;
                            
break;
case 2:return 12;
break;
case 3:   //js
                                yy_.yytext = yy_.yytext.substr(1, yy_.yyleng - 2);
                                /*php $this->yy->text = substr($this->yy->text, 1, strlen($this->yy->text) - 2); */
                                return 32;
                            
break;
case 4:return 22
break;
case 5:return 8
break;
case 6:   //js
                                CodeAssistant.pushPath();
                                // //this.begin('PATH_STATE');
                                ///*php $this->begin('PATH_STATE'); */
                                return 35;
                            
break;
case 7:   //js
                                CodeAssistant.setPath(yy_.yytext);
                                ////this.begin('PATH_STATE');
                                ///*php $this->begin('PATH_STATE'); */
                                return 34;
                            
break;
case 8:/* skip whitespace */
break;
case 9:return 33
break;
case 10:return 15
break;
case 11:return 16
break;
case 12:return 14
break;
case 13:return 13
break;
case 14:return 17
break;
case 15:return 28
break;
case 16:return 29
break;
case 17:return 18
break;
case 18:return 21
break;
case 19:return 20
break;
case 20:return 19
break;
case 21:return 23
break;
case 22:return 24
break;
case 23:return 25
break;
case 24:return 27
break;
case 25:   //js
                                this.popState();
                                /*php $this->popState(); */
                                return 10;
                            
break;
case 26:   //js
                                this.begin('CONTEXT');
                                /*php $this->begin('CONTEXT'); */
                                return '[';
                            
break;
case 27:   //js
                                throw {message: 'Unknown character \'' + yy_.yytext + '\'', line: (yy_.yylineno + 1), column: yy_.yylloc.last_column};
                                /*php throw new Exception('Unknown character \'' . $this->yy->text . '\', line ' . ($this->yy->lineNo + 1) . ' near pos ' . $this->yy->loc->lastColumn); */
                            
break;
case 28:   //js
                                CodeAssistant.setPath(yy_.yytext);
                                //
                                return 34;
                            
break;
case 29:   //js
                                CodeAssistant.pushPath();
                                //
                                return 35;
                            
break;
case 30:   //js
                                CodeAssistant.pushPath();//??
                                //
                                return 'JOIN';
                            
break;
case 31:   //js
                                this.unput(yy_.yytext);
                                this.popState();
                                /*php $this->input = $this->yy->text . $this->input; $this->popState(); */
                            
break;
case 32:   //js
                                CodeAssistant.pathFollowKey();
                                this.popState();
                                /*php $this->popState(); */ return ']';
                            
break;
case 33:return 11;
break;
case 34:return 21;
break;
case 35:return 35;
break;
case 36:return 11;
break;
case 37:   //js
                                throw {message: 'Unknown character \'' + yy_.yytext + '\'', line: (yy_.yylineno + 1), column: yy_.yylloc.last_column};
                                /*php throw new Exception('Unknown character \'' . $this->yy->text . '\', line ' . ($this->yy->lineNo + 1) . ' near pos ' . $this->yy->loc->lastColumn); */
                            
break;
}
},
rules: [/^(?:\{)/,/^(?:%%)/,/^(?:[%][A-Za-z]+)/,/^(?:"[^"]*"|'[^']*')/,/^(?:in\b)/,/^(?:,)/,/^(?:\.)/,/^(?:[A-Za-z_]+)/,/^(?:\s+)/,/^(?:[0-9]+(\.[0-9]+)?\b)/,/^(?:\*)/,/^(?:\/)/,/^(?:-)/,/^(?:\+)/,/^(?:%)/,/^(?:\()/,/^(?:\))/,/^(?:==)/,/^(?:=)/,/^(?:>)/,/^(?:<)/,/^(?:&)/,/^(?:\|)/,/^(?:\^)/,/^(?:!)/,/^(?:\})/,/^(?:\[)/,/^(?:.)/,/^(?:[A-Za-z_]+)/,/^(?:\.)/,/^(?:\+)/,/^(?:.)/,/^(?:\])/,/^(?:[^\]=.{%]+)/,/^(?:=)/,/^(?:\.)/,/^(?:[^{%]+)/,/^(?:.)/],
conditions: {"CONTEXT":{"rules":[0,1,2,32,33,34,35,37],"inclusive":true},"EXPR":{"rules":[2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27],"inclusive":false},"PATH_STATE":{"rules":[26,28,29,30,31],"inclusive":false},"INITIAL":{"rules":[0,1,2,36,37],"inclusive":true}}
});
return lexer;
})();
parser.lexer = lexer;
function Parser () {
  this.yy = {};
}
Parser.prototype = parser;parser.Parser = Parser;
return new Parser;
})();


if (typeof require !== 'undefined' && typeof exports !== 'undefined') {
exports.parser = SmartboardsExpression;
exports.Parser = SmartboardsExpression.Parser;
exports.parse = function () { return SmartboardsExpression.parse.apply(SmartboardsExpression, arguments); };
exports.main = function commonjsMain(args) {
    if (!args[1]) {
        console.log('Usage: '+args[0]+' FILE');
        process.exit(1);
    }
    var source = require('fs').readFileSync(require('path').normalize(args[1]), "utf8");
    return exports.parser.parse(source);
};
if (typeof module !== 'undefined' && require.main === module) {
  exports.main(process.argv.slice(1));
}
}
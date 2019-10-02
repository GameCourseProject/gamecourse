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
var o=function(k,v,o,l){for(o=o||{},l=k.length;l--;o[k[l]]=v);return o},$V0=[1,4],$V1=[1,5],$V2=[1,12],$V3=[1,11],$V4=[1,9],$V5=[1,10],$V6=[1,14],$V7=[1,15],$V8=[1,16],$V9=[1,17],$Va=[1,18],$Vb=[1,19],$Vc=[1,20],$Vd=[1,12,14],$Ve=[1,22],$Vf=[1,23],$Vg=[1,24],$Vh=[1,25],$Vi=[1,26],$Vj=[1,27],$Vk=[1,28],$Vl=[1,29],$Vm=[1,30],$Vn=[1,31],$Vo=[1,32],$Vp=[1,33],$Vq=[8,11,13,15,16,17,18,19,20,21,22,24,25,26,27],$Vr=[8,11,13,15,16,20,21,22,24,25,26,27],$Vs=[8,11,13,20,21,25,26,27],$Vt=[8,11,13,20,21,22,24,25,26,27],$Vu=[8,11,13],$Vv=[8,11,13,15,16,17,18,19,20,21,22,24,25,26,27,38],$Vw=[1,66];
var parser = {trace: function trace() { },
yy: {},
symbols_: {"error":2,"start":3,"block":4,"stmt":5,"args":6,"exp":7,",":8,"arglist":9,"(":10,")":11,"EXPR_START":12,"EXPR_END":13,"TEXT":14,"+":15,"-":16,"*":17,"/":18,"%":19,"EQUALS":20,"<":21,">":22,"=":23,"IN":24,"&":25,"|":26,"^":27,"~":28,"!":29,"function":30,"NULL":31,"TRUE":32,"FALSE":33,"PARAM":34,"STRING":35,"NUMBER":36,"PATH":37,"PATH_SEPARATOR":38,"$accept":0,"$end":1},
terminals_: {2:"error",8:",",10:"(",11:")",12:"EXPR_START",13:"EXPR_END",14:"TEXT",15:"+",16:"-",17:"*",18:"/",19:"%",20:"EQUALS",21:"<",22:">",23:"=",24:"IN",25:"&",26:"|",27:"^",28:"~",29:"!",31:"NULL",32:"TRUE",33:"FALSE",34:"PARAM",35:"STRING",36:"NUMBER",37:"PATH",38:"PATH_SEPARATOR"},
productions_: [0,[3,0],[3,1],[4,1],[4,2],[6,1],[6,3],[9,2],[9,3],[5,3],[5,2],[5,1],[7,3],[7,3],[7,3],[7,3],[7,3],[7,3],[7,3],[7,3],[7,4],[7,4],[7,3],[7,4],[7,4],[7,3],[7,3],[7,3],[7,2],[7,2],[7,2],[7,3],[7,1],[7,1],[7,1],[7,1],[7,1],[7,1],[7,1],[30,3],[30,4],[30,3],[30,4],[30,3],[30,4]],
performAction: function anonymous(yytext, yyleng, yylineno, yy, yystate /* action[1] */, $$ /* vstack */, _$ /* lstack */) {
/* this == yyval */

var $0 = $$.length - 1;
switch (yystate) {
case 1: case 10:
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
case 7:
/*php
            this.$ = null;
        */
break;
case 8: case 9: case 31:
/*php
            this.$ = $$[$0-1].yytext;
        */
break;
case 11:
   //js
            /*php
            this.$ = new ValueNode($$[$0].yytext);
        */
break;
case 12:
/*php
            this.$ = new GenericBinaryOp('+', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 13:
/*php
            this.$ = new GenericBinaryOp('-', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 14:
/*php
            this.$ = new GenericBinaryOp('*', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 15:
/*php
            this.$ =  new GenericBinaryOp('/', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 16:
/*php
            this.$ =  new GenericBinaryOp('%', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 17:
/*php
            this.$ = new GenericBinaryOp('==', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 18:
/*php
            this.$ = new GenericBinaryOp('<', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 19:
/*php
            this.$ = new GenericBinaryOp('>', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 20:
/*php
            this.$ = new GenericBinaryOp('<=', $$[$0-3].yytext, $$[$0].yytext);
        */
break;
case 21:
/*php
            this.$ = new GenericBinaryOp('>=', $$[$0-3].yytext, $$[$0].yytext);
        */
break;
case 22:
/*php
            this.$ = new GenericBinaryOp('in_array', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 23:
/*php
            this.$ = new GenericBinaryOp('&&', $$[$0-3].yytext, $$[$0].yytext);
        */
break;
case 24:
/*php
            this.$ = new GenericBinaryOp('||', $$[$0-3].yytext, $$[$0].yytext);
        */
break;
case 25:
/*php
            this.$ = new GenericBinaryOp('&', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 26:
/*php
            this.$ = new GenericBinaryOp('|', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 27:
/*php
            this.$ = new GenericBinaryOp('||', $$[$0-2].yytext, $$[$0].yytext);
        */
break;
case 28:
/*php
            this.$ = new GenericUnaryOp('~', $$[$0].yytext);
        */
break;
case 29:
/*php
            this.$ = new GenericUnaryOp('!', $$[$0].yytext);
        */
break;
case 30:
/*php
            this.$ = new GenericUnaryOp('-', $$[$0].yytext);
        */
break;
case 32:
   //js
            CodeAssistant.reset();
            /*php this.$ = $$[$0]->text; */
        
break;
case 33:
/*php
            this.$ = new ValueNode(null);
        */
break;
case 34:
/*php
            this.$ = new ValueNode(1);
        */
break;
case 35:
/*php
            this.$ = new ValueNode(0);
        */
break;
case 36:
/*php
            this.$ = new ParameterNode(substr($$[$0].yytext, 1));
        */
break;
case 37:
/*php
            this.$ = new ValueNode($$[$0].yytext);
        */
break;
case 38:
/*php
            this.$ = new ValueNode((int) ($$[$0].yytext));
        */
break;
case 39:
/*php
            this.$ = new FunctionOp($$[$0].yytext, null, $$[$0-2].yytext);
        */
break;
case 40:
/*php
            this.$ = new FunctionOp($$[$0-1].yytext, $$[$0].yytext, $$[$0-3].yytext);
        */
break;
case 41:
/*php
            this.$ = new FunctionOp($$[$0].yytext, null, null,new ParameterNode(substr($$[$0-2].yytext, 1)));
        */
break;
case 42:
/*php
            this.$ = new FunctionOp($$[$0-1].yytext, $$[$0].yytext, null,new ParameterNode(substr($$[$0-3].yytext, 1)));
        */
break;
case 43:
/*php
            this.$ = new FunctionOp($$[$0].yytext, null, null,$$[$0-2].yytext);
        */
break;
case 44:
/*php
            this.$ = new FunctionOp($$[$0-1].yytext, $$[$0].yytext, null,$$[$0-3].yytext);
        */
break;
}
},
table: [{1:[2,1],3:1,4:2,5:3,12:$V0,14:$V1},{1:[3]},{1:[2,2]},{1:[2,3],4:6,5:3,12:$V0,14:$V1},{7:7,10:$V2,13:[1,8],16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},o($Vd,[2,11]),{1:[2,4]},{13:[1,21],15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,20:$Vj,21:$Vk,22:$Vl,24:$Vm,25:$Vn,26:$Vo,27:$Vp},o($Vd,[2,10]),{7:34,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:35,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:36,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:37,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},o($Vq,[2,32],{38:[1,38]}),o($Vq,[2,33]),o($Vq,[2,34]),o($Vq,[2,35]),o($Vq,[2,36],{38:[1,39]}),o($Vq,[2,37]),o($Vq,[2,38]),{38:[1,40]},o($Vd,[2,9]),{7:41,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:42,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:43,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:44,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:45,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:46,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:47,10:$V2,16:$V3,23:[1,48],28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:49,10:$V2,16:$V3,23:[1,50],28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:51,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:53,10:$V2,16:$V3,25:[1,52],28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:55,10:$V2,16:$V3,26:[1,54],28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{7:56,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},o($Vq,[2,28]),o($Vq,[2,29]),o($Vq,[2,30]),{11:[1,57],15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,20:$Vj,21:$Vk,22:$Vl,24:$Vm,25:$Vn,26:$Vo,27:$Vp},{37:[1,58]},{37:[1,59]},{37:[1,60]},o($Vr,[2,12],{17:$Vg,18:$Vh,19:$Vi}),o($Vr,[2,13],{17:$Vg,18:$Vh,19:$Vi}),o($Vq,[2,14]),o($Vq,[2,15]),o($Vq,[2,16]),o([8,11,13,20,25,26,27],[2,17],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,21:$Vk,22:$Vl,24:$Vm}),o($Vs,[2,18],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,22:$Vl,24:$Vm}),{7:61,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},o($Vt,[2,19],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi}),{7:62,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},o($Vt,[2,22],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi}),{7:63,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},o($Vu,[2,25],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,20:$Vj,21:$Vk,22:$Vl,24:$Vm,25:$Vn,26:$Vo,27:$Vp}),{7:64,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},o($Vu,[2,26],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,20:$Vj,21:$Vk,22:$Vl,24:$Vm,25:$Vn,26:$Vo,27:$Vp}),o($Vu,[2,27],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,20:$Vj,21:$Vk,22:$Vl,24:$Vm,25:$Vn,26:$Vo,27:$Vp}),o($Vq,[2,31]),o($Vv,[2,43],{9:65,10:$Vw}),o($Vv,[2,41],{9:67,10:$Vw}),o($Vv,[2,39],{9:68,10:$Vw}),o($Vs,[2,20],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,22:$Vl,24:$Vm}),o($Vt,[2,21],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi}),o($Vu,[2,23],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,20:$Vj,21:$Vk,22:$Vl,24:$Vm,25:$Vn,26:$Vo,27:$Vp}),o($Vu,[2,24],{15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,20:$Vj,21:$Vk,22:$Vl,24:$Vm,25:$Vn,26:$Vo,27:$Vp}),o($Vv,[2,44]),{6:70,7:71,10:$V2,11:[1,69],16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},o($Vv,[2,42]),o($Vv,[2,40]),o($Vv,[2,7]),{11:[1,72]},{8:[1,73],11:[2,5],15:$Ve,16:$Vf,17:$Vg,18:$Vh,19:$Vi,20:$Vj,21:$Vk,22:$Vl,24:$Vm,25:$Vn,26:$Vo,27:$Vp},o($Vv,[2,8]),{6:74,7:71,10:$V2,16:$V3,28:$V4,29:$V5,30:13,31:$V6,32:$V7,33:$V8,34:$V9,35:$Va,36:$Vb,37:$Vc},{11:[2,6]}],
defaultActions: {2:[2,2],6:[2,4],74:[2,6]},
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
                                return 12;
                            
break;
case 1:   //js
                                yy_.yytext = '%';
                                /*php $this->yy->text = '%'; */
                                return 14;
                            
break;
case 2:return 34;
break;
case 3:   //js
                                yy_.yytext = yy_.yytext.substr(1, yy_.yyleng - 2);
                                /*php $this->yy->text = substr($this->yy->text, 1, strlen($this->yy->text) - 2); */
                                return 35;
                            
break;
case 4:return 24
break;
case 5:return 8
break;
case 6:   //js
                                CodeAssistant.pushPath();
                                // //this.begin('PATH_STATE');
                                ///*php $this->begin('PATH_STATE'); */
                                return 38;
                            
break;
case 7:return 31
break;
case 8:return 32
break;
case 9:return 33
break;
case 10:   //js
                                CodeAssistant.setPath(yy_.yytext);
                                ////this.begin('PATH_STATE');
                                ///*php $this->begin('PATH_STATE'); */
                                return 37;
                            
break;
case 11:/* skip whitespace */
break;
case 12:return 36
break;
case 13:return 17
break;
case 14:return 18
break;
case 15:return 16
break;
case 16:return 15
break;
case 17:return 19
break;
case 18:return 10
break;
case 19:return 11
break;
case 20:return 20
break;
case 21:return 23
break;
case 22:return 22
break;
case 23:return 21
break;
case 24:return 25
break;
case 25:return 26
break;
case 26:return 27
break;
case 27:return 29
break;
case 28:   //js
                                this.popState();
                                /*php $this->popState(); */
                                return 13;
                            
break;
case 29:   //js
                                throw {message: 'Unknown character \'' + yy_.yytext + '\'', line: (yy_.yylineno + 1), column: yy_.yylloc.last_column};
                                /*php throw new Exception('Unknown character \'' . $this->yy->text . '\', line ' . ($this->yy->lineNo + 1) . ' near pos ' . $this->yy->loc->lastColumn); */
                            
break;
case 30:   //js
                                CodeAssistant.setPath(yy_.yytext);
                                //
                                return 37;
                            
break;
case 31:   //js
                                CodeAssistant.pushPath();
                                //
                                return 38;
                            
break;
case 32:   //js
                                this.unput(yy_.yytext);
                                this.popState();
                                /*php $this->input = $this->yy->text . $this->input; $this->popState(); */
                            
break;
case 33:return 14;
break;
case 34:   //js
                                throw {message: 'Unknown character \'' + yy_.yytext + '\'', line: (yy_.yylineno + 1), column: yy_.yylloc.last_column};
                                /*php throw new Exception('Unknown character \'' . $this->yy->text . '\', line ' . ($this->yy->lineNo + 1) . ' near pos ' . $this->yy->loc->lastColumn); */
                            
break;
}
},
rules: [/^(?:\{)/,/^(?:%%)/,/^(?:[%][A-Za-z]+)/,/^(?:"[^"]*"|'[^']*')/,/^(?:in\b)/,/^(?:,)/,/^(?:\.)/,/^(?:null\b)/,/^(?:true\b)/,/^(?:false\b)/,/^(?:[A-Za-z_]+)/,/^(?:\s+)/,/^(?:[0-9]+(\.[0-9]+)?\b)/,/^(?:\*)/,/^(?:\/)/,/^(?:-)/,/^(?:\+)/,/^(?:%)/,/^(?:\()/,/^(?:\))/,/^(?:==)/,/^(?:=)/,/^(?:>)/,/^(?:<)/,/^(?:&)/,/^(?:\|)/,/^(?:\^)/,/^(?:!)/,/^(?:\})/,/^(?:.)/,/^(?:[A-Za-z_]+)/,/^(?:\.)/,/^(?:.)/,/^(?:[^{]+)/,/^(?:.)/],
conditions: {"CONTEXT":{"rules":[0,1,2,34],"inclusive":true},"EXPR":{"rules":[2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29],"inclusive":false},"PATH_STATE":{"rules":[30,31,32],"inclusive":false},"INITIAL":{"rules":[0,1,33,34],"inclusive":true}}
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
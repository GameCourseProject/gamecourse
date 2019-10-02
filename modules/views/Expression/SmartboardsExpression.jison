/* lexical grammar */
%lex
%x EXPR PATH_STATE
%s CONTEXT
%%

"{"                         {   //js
                                this.begin('EXPR');
                                /*php $this->begin('EXPR'); */
                                return 'EXPR_START';
                            }
"%%"                        {   //js
                                yytext = '%';
                                /*php $this->yy->text = '%'; */
                                return 'TEXT';
                            }
<EXPR,CONTEXT>[%][A-Za-z]+    return 'PARAM';
<EXPR>\"[^"]*\"|\'[^']*\'   {   //js
                                yytext = yytext.substr(1, yyleng - 2);
                                /*php $this->yy->text = substr($this->yy->text, 1, strlen($this->yy->text) - 2); */
                                return 'STRING';
                            }
<EXPR>"in"                  return 'IN'
<EXPR>','                   return ','
<EXPR>"."                   {   //js
                                CodeAssistant.pushPath();
                                // //this.begin('PATH_STATE');
                                ///*php $this->begin('PATH_STATE'); */
                                return 'PATH_SEPARATOR';
                            }
<EXPR>"null"                return 'NULL'
<EXPR>"true"                return 'TRUE'
<EXPR>"false"               return 'FALSE'
<EXPR>[A-Za-z_]+             {   //js
                                CodeAssistant.setPath(yytext);
                                ////this.begin('PATH_STATE');
                                ///*php $this->begin('PATH_STATE'); */
                                return 'PATH';
                            }
<EXPR>\s+                   /* skip whitespace */
<EXPR>[0-9]+("."[0-9]+)?\b  return 'NUMBER'
<EXPR>"*"                   return '*'
<EXPR>"/"                   return '/'
<EXPR>"-"                   return '-'
<EXPR>"+"                   return '+'
<EXPR>"%"                   return '%'
<EXPR>"("                   return '('
<EXPR>")"                   return ')'
<EXPR>"=="                  return 'EQUALS'
<EXPR>"="                   return '='
<EXPR>">"                   return '>'
<EXPR>"<"                   return '<'
<EXPR>"&"                   return '&'
<EXPR>"|"                   return '|'
<EXPR>"^"                   return '^'
<EXPR>"!"                   return '!'
<EXPR>"}"                   {   //js
                                this.popState();
                                /*php $this->popState(); */
                                return 'EXPR_END';
                            }
<EXPR>.                     {   //js
                                throw {message: 'Unknown character \'' + yytext + '\'', line: (yylineno + 1), column: yylloc.last_column};
                                /*php throw new Exception('Unknown character \'' . $this->yy->text . '\', line ' . ($this->yy->lineNo + 1) . ' near pos ' . $this->yy->loc->lastColumn); */
                            }
<PATH_STATE>[A-Za-z_]+      {   //js
                                CodeAssistant.setPath(yytext);
                                //
                                return 'PATH';
                            }
<PATH_STATE>"."             {   //js
                                CodeAssistant.pushPath();
                                //
                                return 'PATH_SEPARATOR';
                            }
<PATH_STATE>.               {   //js
                                this.unput(yytext);
                                this.popState();
                                /*php $this->input = $this->yy->text . $this->input; $this->popState(); */
                            }
<INITIAL>[^{]+             return 'TEXT';
.                           {   //js
                                throw {message: 'Unknown character \'' + yytext + '\'', line: (yylineno + 1), column: yylloc.last_column};
                                /*php throw new Exception('Unknown character \'' . $this->yy->text . '\', line ' . ($this->yy->lineNo + 1) . ' near pos ' . $this->yy->loc->lastColumn); */
                            }

/lex

/* operator associations and precedence */

%left LOGICAL_OR
%left LOGICAL_AND
%left BITWISE_OR
%left BITWISE_XOR
%left BITWISE_AND
%left '|'
%left '^'
%left '&'
%left EQUALS
%left '='
%left '<'
%left '>' IN
%left '+' '-'
%left '*' '/' '%'
%right UMINUS

%start start

%% /* language grammar */

//option namespace:Modules\Views\Expression
//option class:ExpressionEvaluatorBase
//option fileName:ExpressionEvaluatorBase.php

start
    : /* empty */
        {   //js
            /*php return new ValueNode(''); */
        }
    | block
        {   //js
            /*php return $1.yytext; */
        }
    ;

block
    : stmt
        {/*php
            $$ = $1.yytext;
        */}
    | stmt block
        {/*php
            $$ = new StatementSequence($1.yytext, $2.yytext);
        */}
    ;

args
    : exp 
        {/*php
            $$ = new ArgumentSequence($1.yytext);
        */}
    |  exp ',' args 
        {/*php
            $$ = new ArgumentSequence($1.yytext, $3.yytext);
        */}
    ;
arglist
    : '(' ')'
        {/*php
            $$ = null;
        */}
    | '(' args ')'
        {/*php
            $$ = $2.yytext;
        */}
    ;
stmt
    : EXPR_START exp EXPR_END
        {/*php
            $$ = $2.yytext;
        */}
    | EXPR_START EXPR_END
        {   //js
            /*php return new ValueNode(''); */
        }
    | TEXT
        {   //js
            /*php
            $$ = new ValueNode($1.yytext);
        */}
    ;
exp
    : exp '+' exp
        {/*php
            $$ = new GenericBinaryOp('+', $1.yytext, $3.yytext);
        */}
    | exp '-' exp
        {/*php
            $$ = new GenericBinaryOp('-', $1.yytext, $3.yytext);
        */}
    | exp '*' exp
        {/*php
            $$ = new GenericBinaryOp('*', $1.yytext, $3.yytext);
        */}
    | exp '/' exp
        {/*php
            $$ =  new GenericBinaryOp('/', $1.yytext, $3.yytext);
        */}
    | exp '%' exp
        {/*php
            $$ =  new GenericBinaryOp('%', $1.yytext, $3.yytext);
        */}
    | exp 'EQUALS' exp
        {/*php
            $$ = new GenericBinaryOp('==', $1.yytext, $3.yytext);
        */}
    | exp '<' exp
        {/*php
            $$ = new GenericBinaryOp('<', $1.yytext, $3.yytext);
        */}
    | exp '>' exp
        {/*php
            $$ = new GenericBinaryOp('>', $1.yytext, $3.yytext);
        */}
    | exp '<' '=' exp
        {/*php
            $$ = new GenericBinaryOp('<=', $1.yytext, $4.yytext);
        */}
    | exp '>'  '=' exp
        {/*php
            $$ = new GenericBinaryOp('>=', $1.yytext, $4.yytext);
        */}
    | exp 'IN' exp
        {/*php
            $$ = new GenericBinaryOp('in_array', $1.yytext, $3.yytext);
        */}
    | exp '&' '&' exp %prec LOGICAL_AND
        {/*php
            $$ = new GenericBinaryOp('&&', $1.yytext, $4.yytext);
        */}
    | exp '|' '|' exp %prec LOGICAL_OR
        {/*php
            $$ = new GenericBinaryOp('||', $1.yytext, $4.yytext);
        */}
    | exp '&' exp %prec BITWISE_AND
        {/*php
            $$ = new GenericBinaryOp('&', $1.yytext, $3.yytext);
        */}
    | exp '|' exp %prec BITWISE_OR
        {/*php
            $$ = new GenericBinaryOp('|', $1.yytext, $3.yytext);
        */}
    | exp '^' exp %prec BITWISE_XOR
        {/*php
            $$ = new GenericBinaryOp('||', $1.yytext, $3.yytext);
        */}
    | '~' exp %prec UMINUS
        {/*php
            $$ = new GenericUnaryOp('~', $2.yytext);
        */}
    | '!' exp %prec UMINUS
        {/*php
            $$ = new GenericUnaryOp('!', $2.yytext);
        */}
    | '-' exp %prec UMINUS
        {/*php
            $$ = new GenericUnaryOp('-', $2.yytext);
        */}
    | '(' exp ')'
        {/*php
            $$ = $2.yytext;
        */}
    | function
        {   //js
            CodeAssistant.reset();
            /*php $$ = $1->text; */
        }
    | NULL
        {/*php
            $$ = new ValueNode(null);
        */}
    | TRUE
        {/*php
            $$ = new ValueNode(1);
        */}
    | FALSE
        {/*php
            $$ = new ValueNode(0);
        */}
    | PARAM
        {/*php
            $$ = new ParameterNode(substr($1.yytext, 1));
        */}
    | STRING
        {/*php
            $$ = new ValueNode($1.yytext);
        */}
    | NUMBER
        {/*php
            $$ = new ValueNode((int) ($1.yytext));
        */}   
    ;
function
    : PATH PATH_SEPARATOR PATH
        {/*php
            $$ = new FunctionOp($3.yytext, null, $1.yytext);
        */}
    | PATH PATH_SEPARATOR PATH arglist 
        {/*php
            $$ = new FunctionOp($3.yytext, $4.yytext, $1.yytext);
        */}
    | PARAM PATH_SEPARATOR PATH
        {/*php
            $$ = new FunctionOp($3.yytext, null, null,new ParameterNode(substr($1.yytext, 1)));
        */}
    | PARAM PATH_SEPARATOR PATH  arglist 
        {/*php
            $$ = new FunctionOp($3.yytext, $4.yytext, null,new ParameterNode(substr($1.yytext, 1)));
        */}
    | function PATH_SEPARATOR PATH
        {/*php
            $$ = new FunctionOp($3.yytext, null, null,$1.yytext);
        */}
    | function PATH_SEPARATOR PATH  arglist 
        {/*php
            $$ = new FunctionOp($3.yytext, $4.yytext, null,$1.yytext);
        */}
    ;

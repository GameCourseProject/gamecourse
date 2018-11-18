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
<INITIAL,EXPR,CONTEXT>[%][A-Za-z]+    return 'PARAM';
<EXPR>\"[^"]*\"|\'[^']*\'   {   //js
                                yytext = yytext.substr(1, yyleng - 2);
                                /*php $this->yy->text = substr($this->yy->text, 1, strlen($this->yy->text) - 2); */
                                return 'STRING';
                            }
<EXPR>"in"                  return 'IN'
<EXPR>[$][A-Za-z]+          return 'FUNCTION'
<EXPR>','                   return ','
<EXPR>"."                   {   //js
                                CodeAssistant.pushPath();
                                this.begin('PATH_STATE');
                                /*php $this->begin('PATH_STATE'); */
                                return 'PATH_SEPARATOR';
                            }
<EXPR>[A-Za-z]+             {   //js
                                CodeAssistant.setPath(yytext);
                                this.begin('PATH_STATE');
                                /*php $this->begin('PATH_STATE'); */
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
<EXPR,PATH_STATE>"["        {   //js
                                this.begin('CONTEXT');
                                /*php $this->begin('CONTEXT'); */
                                return '[';
                            }
<EXPR>.                     {   //js
                                throw {message: 'Unknown character \'' + yytext + '\'', line: (yylineno + 1), column: yylloc.last_column};
                                /*php throw new Exception('Unknown character \'' . $this->yy->text . '\', line ' . ($this->yy->lineNo + 1) . ' near pos ' . $this->yy->loc->lastColumn); */
                            }
<PATH_STATE>[A-Za-z]+       {   //js
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
<CONTEXT>"]"                {   //js
                                CodeAssistant.pathFollowKey();
                                this.popState();
                                /*php $this->popState(); */ return ']';
                            }
<CONTEXT>[^\]{%]+           return 'TEXT';
<INITIAL>[^{%]+             return 'TEXT';
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

arglist
    : exp
        {/*php
            $$ = new ArgumentSequence($1.yytext);
        */}
    | exp ',' arglist
        {/*php
            $$ = new ArgumentSequence($1.yytext, $3.yytext);
        */}
    ;
stmt
    : EXPR_START exp EXPR_END
        {/*php
            $$ = $2.yytext;
        */}
    | TEXT
        {   //js
            /*php
            $$ = new ValueNode($1.yytext);
        */}
    | PARAM
        {/*php
            $$ = new ParameterNode(substr($1.yytext, 1));
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
    | FUNCTION '(' ')'
        {/*php
            $$ = new FunctionOp(substr($1.yytext, 1), null);
        */}
    | FUNCTION '(' arglist ')'
        {/*php
            $$ = new FunctionOp(substr($1.yytext, 1), $3.yytext);
        */}
    | '(' exp ')'
        {/*php
            $$ = $2.yytext;
        */}
    | totalpath
        {   //js
            CodeAssistant.reset();
            /*php $$ = $1->text; */
        }
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

simplepath
    : PATH
        {   //js
            $$ = $1;
            /*php $$ = $1->text; */
        }
    | PATH PATH_SEPARATOR simplepath
        {   //js
            $$ = $1 + '.' + $3;
            /*php $$ = $1->text . '.' . $3->text; */
        }
    ;

context
    : '[' block ']'
        {/*php
            $$ = new ContextSequence($2.yytext);
        */}
    | '[' block ']' context
        {/*php
            $$ = new ContextSequence($2.yytext, $4.yytext);
        */}
    ;

contextpath
    : simplepath
        {/*php
            $$ = new DatabasePath($1->text);
        */}
    | simplepath context
        {/*php
            $$ = new DatabasePath($1->text, $2->text);
        */}
    | simplepath context PATH_SEPARATOR contextpath
        {/*php
            $$ = new DatabasePath($1->text, $2->text, $4->text);
        */}
    ;

totalpath
    : PARAM PATH_SEPARATOR contextpath
        {/*php
            $$ = new DatabasePathFromParameter(substr($1.yytext, 1), $3->text);
        */}
    | PARAM context
        {/*php
            $$ = new DatabasePathFromParameter(substr($1.yytext, 1), null, $2->text);
        */}
    | PARAM context PATH_SEPARATOR contextpath
        {/*php
            $$ = new DatabasePathFromParameter(substr($1.yytext, 1), $4->text, $2->text);
        */}
    | contextpath
        {/*php
            $$ = $1->text;
        */}
    ;

<?php declare(strict_types = 1);

namespace Sbludufunk\Randown;

use Sbludufunk\Randown\Tokens\BlockEndToken;
use Sbludufunk\Randown\Tokens\BlockStartToken;
use Sbludufunk\Randown\Tokens\EscapeToken;
use Sbludufunk\Randown\Tokens\FunctionCallToken;
use Sbludufunk\Randown\Tokens\MethodCallToken;
use Sbludufunk\Randown\Tokens\BlockSeparatorToken;
use Sbludufunk\Randown\Tokens\TextToken;
use Sbludufunk\Randown\Tokens\ReferenceToken;
use function array_column;
use function preg_match;
use const PREG_SPLIT_NO_EMPTY;

class Tokenizer
{
    private $_patterns;

    private $_splitPattern;

    public function __construct(){
        $this->_patterns = [];

        $this->_patterns[] = [
            EscapeToken::CLASS,
            "\\\\    [\\\\{}@*|&$]", // TODO add $
            "\\\\   ([\\\\{}@*|&$])"
        ];

        $this->_patterns[] = [
            ReferenceToken::CLASS,
            "\\$    .*?    \\$",
            "\\$   (.*?)   \\$"
        ];

        $this->_patterns[] = [
            FunctionCallToken::CLASS,
            " \\s*    \\&    \\s*    \\{",
            "(\\s*)   \\&   (\\s*)   \\{"
        ];

        $this->_patterns[] = [
            MethodCallToken::CLASS,
            " \\s*    \\&    \\s*     ..*?     \\s*    \\{",
            "(\\s*)   \\&   (\\s*)   (..*?)   (\\s*)   \\{"
        ];

        $this->_patterns[] = [
            BlockSeparatorToken::CLASS,
            "\\*    [0-9]+    \\|",
            "\\*   ([0-9]+)   \\|"
        ];

        $this->_patterns[] = [
            BlockEndToken::CLASS,
            "\\*    [0-9]+    \\}",
            "\\*   ([0-9]+)   \\}"
        ];

        $this->_patterns[] = [BlockSeparatorToken::CLASS,  "\\|", "\\|"];
        $this->_patterns[] = [BlockStartToken::CLASS, "\\{", "\\{"];
        $this->_patterns[] = [BlockEndToken::CLASS,   "\\}", "\\}"];

        $this->_splitPattern = implode("|", array_column($this->_patterns, 1));
    }

    public function tokenize(String $document){

        $rawTokens = preg_split(
            "/(" . $this->_splitPattern . ")/xsD", $document, 0,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $tokens = [];
        for($i = 0, $countTokens = count($rawTokens); $i < $countTokens; $i++){
            $rawToken = $rawTokens[$i];
            foreach($this->_patterns as [$class, $splitPattern, $capturePattern]){
                $bits = [];
                if(preg_match("/^" . $capturePattern . "$/xsD", $rawToken, $bits) === 1){
                    $tokens[] = new $class(...array_slice($bits, 1));
                    continue 2;
                }
            }
            $tokens[] = new TextToken($rawToken);
        }

        return $tokens;
    }
}

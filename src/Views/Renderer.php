<?php

namespace Views;

class Renderer
{

    public static function render(
        $vista,
        $datos,
        $layoutFile = "layout.view.tpl",
        $render = true
    ) {
        if (!is_array($datos)) {
            http_response_code(404);
            die("Error de renderizador: datos no es un arreglo");
        }

        $global_context = \Utilities\Context::getContext();
        if (is_array($global_context)) {
            $datos = array_merge($global_context, $datos);
        }
        if (isset($_SESSION["login"]) && is_array($_SESSION["login"])) {
            $datos = array_merge(array("login" => $_SESSION["login"]), $datos);
        }
        if (
            isset($datos["layoutFile"])
            && is_string($datos["layoutFile"])
            && preg_match('#^[A-Za-z0-9_/-]+$#', $datos["layoutFile"])
            && strpos($datos["layoutFile"], "..") === false
            && $layoutFile === "layout.view.tpl"
        ) {
            $layoutFile = $datos["layoutFile"];
        }
        if (strpos($layoutFile, ".view.tpl") === false) {
            $layoutFile .= ".view.tpl";
        }

        $viewsPath = __DIR__ . "/templates/";
        $fileTemplate = $vista . ".view.tpl";
        $htmlContent = "";
        if (file_exists($viewsPath . $layoutFile)) {
            $htmlContent = file_get_contents($viewsPath . $layoutFile);
            if (file_exists($viewsPath . $fileTemplate)) {
                $tmphtml = file_get_contents($viewsPath . $fileTemplate);
                $htmlContent = str_replace(
                    "{{{page_content}}}",
                    $tmphtml,
                    $htmlContent
                );
                if (strpos($htmlContent, "{{include") !== false) {
                    $htmlContent = self::loadPartials($htmlContent);
                }
                $template_code = self::_parseTemplate($htmlContent);
                $htmlResult = self::_renderTemplate($template_code, $datos);

                if ($render) {
                    if(\Utilities\Context::getContextByKey("USE_URLREWRITE") == "1") {
                        echo self::rewriteUrl($htmlResult);
                    } else {
                        echo $htmlResult;
                    }
                } else {
                    return $htmlResult;
                }
            }
        }
    }

    private static function _renderTemplate(
        $template_block,
        $context,
        $parent = null,
        $root = null
    ) {
        $renderedHTML = "";
        $foreachIsOpen = false;
        $ifIsOpen = false;
        $ifCondition = false;
        $ifNotIsOpen = false;
        $ifNotCondition = false;
        $withIsOpen = false;
        $innerBlock = array();
        $currentContext = "";

        if ($parent === null) {
            $parent = $context;
        }
        if ($root === null) {
            $root = $context;
        }

        foreach ($template_block as $node) {
            if (strpos($node, "{{endwith $currentContext}}") !== false) {
                if ($withIsOpen) {
                    $withIsOpen = false;
                    if (strpos($currentContext, "~") !== false) {
                        $withContext = $root[str_replace("~", "", $currentContext)];
                    } elseif (strpos($currentContext, "&") !== false) {
                        $withContext = $parent[str_replace("&", "", $currentContext)];
                    } else {
                        $withContext = $context[str_replace("&", "", $currentContext)];
                    }
                    $renderedHTML .=
                        self::_renderTemplate(
                            $innerBlock,
                            $withContext,
                            $context,
                            $root
                        );
                    $innerBlock = array();
                    $currentContext = "";
                    continue;
                }
            }
            if (strpos($node, "{{endfor $currentContext}}") !== false) {
                if ($foreachIsOpen) {
                    $foreachIsOpen = false;
                    if (strpos($currentContext, "~") !== false) {
                        $currentContext = str_replace("~", "", $currentContext);
                        foreach ($root[$currentContext] as $forcontext) {
                            $renderedHTML .= self::_renderTemplate(
                                $innerBlock,
                                $forcontext,
                                $context,
                                $root
                            );
                        }
                    } elseif (strpos($currentContext, "&") !== false) {
                        $currentContext = str_replace("&", "", $currentContext);
                        foreach ($parent[$currentContext] as $forcontext) {
                            $renderedHTML .= self::_renderTemplate(
                                $innerBlock,
                                $forcontext,
                                $context,
                                $root
                            );
                        }
                    } else {
                        if (isset($context[$currentContext])) {
                            foreach ($context[$currentContext] as $forcontext) {
                                $renderedHTML .= self::_renderTemplate(
                                    $innerBlock,
                                    $forcontext,
                                    $context,
                                    $root
                                );
                            }
                        }
                    }

                    $innerBlock = array();
                    $currentContext = "";
                    continue;
                }
            }

            if (strpos($node, "{{endifnot $currentContext}}") !== false) {
                if ($ifNotIsOpen) {
                    $ifNotIsOpen = false;
                    $renderedHTML .= ($ifNotCondition) ?
                        self::_renderTemplate(
                            $innerBlock,
                            $context,
                            $parent,
                            $root
                        ) : "";
                    $currentContext = "";
                    $innerBlock = array();
                    $ifNotCondition = false;
                    continue;
                }
            }

            if (strpos($node, "{{endif $currentContext}}") !== false) {
                if ($ifIsOpen) {
                    $ifIsOpen = false;
                    $renderedHTML .= ($ifCondition) ?
                        self::_renderTemplate(
                            $innerBlock,
                            $context,
                            $parent,
                            $root
                        ) : "";
                    $currentContext = "";
                    $innerBlock = array();
                    $ifCondition = false;
                    continue;
                }
            }

            if ($foreachIsOpen || $ifIsOpen || $ifNotIsOpen || $withIsOpen) {
                $innerBlock[] = $node;
                continue;
            }

            if (strpos($node, "{{with") !== false) {
                if (!$withIsOpen) {
                    $withIsOpen = true;
                    $currentContext = trim(
                        str_replace("}}", "", str_replace("{{with", "", $node))
                    );
                    continue;
                }
            }

            if (strpos($node, "{{foreach") !== false) {
                if (!$foreachIsOpen) {
                    $foreachIsOpen = true;
                    $currentContext = trim(
                        str_replace("}}", "", str_replace("{{foreach", "", $node))
                    );
                    continue;
                }
            }
            if (strpos($node, "{{ifnot")  !== false) {
                if (!$ifNotIsOpen) {
                    $ifNotIsOpen = true;
                    $currentContext = trim(
                        str_replace("}}", "", str_replace("{{ifnot", "", $node))
                    );
                    $ifNotCondition = false;
                    if (strpos($currentContext, "~") !== false) {
                        $tmpCurrentContext = str_replace("~", "", $currentContext);
                        if (isset($root[$tmpCurrentContext])) {
                            $ifNotCondition = ($root[$tmpCurrentContext]) == false;
                        }
                    } elseif (strpos($currentContext, "&") !== false) {
                        $tmpCurrentContext = str_replace("&", "", $currentContext);
                        if (isset($parent[$tmpCurrentContext])) {
                            $ifNotCondition = ($parent[$tmpCurrentContext]) == false;
                        }
                    } else {
                        if (isset($context[$currentContext])) {
                            $ifNotCondition = ($context[$currentContext]) == false;
                        }
                    }
                    continue;
                }
            }

            if (strpos($node, "{{if")  !== false) {
                if (!$ifIsOpen) {
                    $ifIsOpen = true;
                    $currentContext = trim(
                        str_replace("}}", "", str_replace("{{if", "", $node))
                    );
                    $ifCondition = false;
                    if (strpos($currentContext, "~") !== false) {
                        $tmpCurrentContext = str_replace("~", "", $currentContext);
                        if (isset($root[$tmpCurrentContext])) {
                            $ifCondition = ($root[$tmpCurrentContext]) && true;
                        }
                    } elseif (strpos($currentContext, "&") !== false) {
                        $tmpCurrentContext = str_replace("&", "", $currentContext);
                        if (isset($parent[$tmpCurrentContext])) {
                            $ifCondition = ($parent[$tmpCurrentContext])  && true;
                        }
                    } else {
                        if (isset($context[$currentContext])) {
                            $ifCondition = ($context[$currentContext]) && true;
                        }
                    }
                    continue;
                }
            }

            $nodeReplace = preg_split(
                "/(\{\{[&,~!]{0,2}\w*\}\})/",
                $node,
                -1,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            );
            foreach ($nodeReplace as $item) {
                $escape = false;
                if (strpos($item, "{{")  !== false) {
                    $index = trim(
                        str_replace("}}", "", str_replace("{{", "", $item))
                    );
                    // El prefijo ! indica que el valor debe escaparse antes de imprimirse.
                    if (strpos($index, "!") !== false) {
                        $escape = true;
                        $index = str_replace("!", "", $index);
                    }
                    if (strpos($index, "~") !== false) {
                        $index = str_replace("~", "", $index);
                        if ($index === "this" && !(is_array($root))) {
                            $item = $root;
                        } else {
                            $item = isset($root[$index]) ? $root[$index] : "";
                        }
                    } elseif (strpos($index, "&") !== false) {
                        $index = str_replace("&", "", $index);
                        if ($index === "this" && !(is_array($parent))) {
                            $item = $parent;
                        } else {
                            $item = isset($parent[$index]) ? $parent[$index] : "";
                        }
                    } else {
                        if ($index === "this" && !(is_array($context))) {
                            $item = $context;
                        } else {
                            $item = isset($context[$index]) ? $context[$index] : "";
                        }
                    }
                }
                if ($escape) {
                    $item = self::_escape($item);
                }
                $renderedHTML .= $item;
            }
        }
        return $renderedHTML;
    }

    private static function _escape($value): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function _parseTemplate($htmlTemplate)
    {
        $regexp_array = array(
          'foreach'      => '(\{\{foreach [~&]?\w*\}\})',
          'endfor'       => '(\{\{endfor [~&]?\w*\}\})',
          'if'           => '(\{\{if [~&]?\w*\}\})',
          'if_not'       => '(\{\{ifnot [~&]?\w*\}\})',
          'if_close'     => '(\{\{endif [~&]?\w*\}\})',
          'ifnot_close'  => '(\{\{endifnot [~&]?\w*\}\})',
          'with'         => '(\{\{with [~&]?\w*\}\})',
          'with_close'   => '(\{\{endwith [~&]?\w*\}\})'
        );

        $tag_regexp = "/" . join("|", $regexp_array) . "/";

        $template_code = preg_split(
            $tag_regexp,
            $htmlTemplate,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        return $template_code;
    }

    private static function loadPartials($htmlTemplate)
    {
        $regexp_array = array(
            'includes '      => '(\{\{include [\w\/]*\}\})',
        );

        $tag_regexp = "/" . join("|", $regexp_array) . "/";

        $template_code = preg_split(
            $tag_regexp,
            $htmlTemplate,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        $htmlBuffer = "";
        foreach($template_code as $block) {
            if (strpos($block, "include")) {
                $filePath = trim(
                        str_replace("}}", "", str_replace("{{include", "", $block))
                ). ".view.tpl";;
                $viewsPath = __DIR__ . "/templates/";
                if (file_exists($viewsPath . $filePath)) {
                    $htmlContent = file_get_contents($viewsPath . $filePath);
                    $htmlBuffer .= $htmlContent;
                } else {
                    $htmlBuffer .= $block;
                }
            } else {
                $htmlBuffer .= $block;
            }
        }
        return $htmlBuffer;
    }

    public static function rewriteUrl($htmlTemplate)
    {
        $regexp_array = array(
            'page '      => '(index.php\??[\w=&]*)',
        );

        $tag_regexp = "/" . join("|", $regexp_array) . "/";

        $template_code = preg_split(
            $tag_regexp,
            $htmlTemplate,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        $htmlBuffer = "";
        $basedir = \Utilities\Context::getContextByKey("BASE_DIR");
        foreach ($template_code as $node) {
            if (strpos($node, "index.php?page=")  !== false) {
                $pageStart = strpos($node, "=") + 1;
                $pageEnd = strpos($node, "&")?:strlen($node);
                $pageValueLength = $pageEnd - $pageStart;
                $page = substr($node, $pageStart, $pageValueLength);
                $query = substr($node, $pageEnd + 1);

                $url = $basedir . "/" . str_replace(array("_",".","-"), "/", $page);
                $url .= strlen($query)?"/?".$query:"/";
                $htmlBuffer .= $url;
            } else {
                if ($node == "index.php") {
                    $htmlBuffer .=  $basedir . "/index";
                } else {
                    $htmlBuffer .= $node;
                }
            }
        }
        return $htmlBuffer;
    }

    private function __construct()
    {

    }
}

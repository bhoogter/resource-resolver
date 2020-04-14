<?xml version='1.0' encoding='ISO-8859-1'?>

<xsl:stylesheet version='1.0' 
    xmlns:xsl='http://www.w3.org/1999/XSL/Transform' 
    xmlns:php="http://php.net/xsl" xsl:extension-element-prefixes="php">
    <xsl:output method='html' cdata-section-elements="script style pre"/>
    <xsl:preserve-space elements="script style pre"/>

    <xsl:variable name='siteSettings' select='php:functionString("page_render::site_settings_dom")' />
    <xsl:variable name='gTitle' select="$siteSettings/*/global/title" />
    <xsl:variable name='bAppendSiteTitle' select='$siteSettings/*/global/title/@append' />

    <xsl:variable name='pTemplate' select='php:functionString("page_render::template_dom")' />
    <xsl:variable name='SRC' select='.' />
    <xsl:variable name='HandledElements' select='php:functionString("page_render::handler_list")'/>

    <xsl:template match='/'>
        <xsl:variable name='debug_make_page' select='php:functionString("constant", "page_render::DEBUG_MAKE_PAGE")' />
        <xsl:if test="$debug_make_page != ''">
            <table class='DEBUG' style='border:solid 1px black;'>
                <tr><td colspan='2' class='title'>make-page.xsl</td></tr>
                <tr><th>Var</th><th>Val</th></tr>
                <tr><td>speTemplate</td><td><xsl:value-of select='$pTemplate'/></td></tr>
                <tr><td>gTitle</td><td><xsl:value-of select='$gTitle'/></td></tr>
                <tr><td>bAppendSiteTitle</td><td><xsl:value-of select='$bAppendSiteTitle'/></td></tr>
                <tr><td>tTitle</td><td><xsl:value-of select='$pTemplate/*/title'/></td></tr>
                <tr><td>lTitle</td><td><xsl:value-of select='$SRC/*/@title'/></td></tr>
                <tr><td>Handled</td><td><xsl:value-of select='$HandledElements'/></td></tr>
            </table>
        </xsl:if>

        <xsl:variable name='pBody' select='$pTemplate//pagebody' />

        <xsl:variable name='pTitle'>
            <xsl:value-of select='$SRC/pagedef/@title' />
            <xsl:if test='$pTemplate/*/title!=""'> - <xsl:value-of select='$pTemplate/*/title' /></xsl:if>
            <xsl:if test='$SRC/pagedef/@title!="" and $bAppendSiteTitle!="" and $gTitle!=""'> - <xsl:value-of select='$gTitle' /></xsl:if>
        </xsl:variable>

        <html>
            <head>
                <xsl:variable name='pageGenerator' select="php:functionString('page_render::generator_name')" />
                <meta name="generator" content="{$pageGenerator}" />
                <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

                <xsl:variable name='pageDesc'>
                    <xsl:value-of select='$SRC/pagedef/@description' />
                    <xsl:if test='string-length($SRC/pagedef/@description) = 0'>
                        <xsl:value-of select="$pTemplate/desc" />
                    </xsl:if>
                    <xsl:if test='//desc = "" and $pTemplate/desc = ""'>
                        <xsl:value-of select="$siteSettings/global/desription" />
                    </xsl:if>
                </xsl:variable>
                <xsl:if test='string-length($pageDesc)!=0'><meta name="description" content="{$pageDesc}"/></xsl:if>

                <xsl:variable name='pageKeywords'>
                    <xsl:value-of select='//@keywords' />
                    <xsl:if test='$SRC/pagedef/@keywords = ""'>
                        <xsl:value-of select="$pTemplate/@keywords" />
                    </xsl:if>
                    <xsl:if test='//@keywords = "" and $pTemplate/@keywords = ""'>
                        <xsl:value-of select="$siteSettings/global/keywords" />
                    </xsl:if>
                </xsl:variable>
                <xsl:if test='string-length($pageKeywords)!=0'>
                    <meta name="keywords" content="{$pageKeywords}"/>
                </xsl:if>

                <title><xsl:value-of select='$pTitle' /></title>
<!--
			<xsl:variable name='systemStyles' select="php:function('juniper_get_styles')" />
			<xsl:for-each select="$systemStyles/*/link"><xsl:copy-of select='.'/></xsl:for-each>
-->
                <xsl:for-each select='$siteSettings/*/global/css | //*/css | $pTemplate/*/css'>
                    <xsl:variable name='location'><xsl:if test='name(..) = "pagetemplate"'>template</xsl:if></xsl:variable>
                    <xsl:variable name='href' select='php:functionString("page_render::resolve_ref", string(@src), string($location), string($SRC/pagedef/@template))' />
                    <xsl:if test='string-length($href) != 0'>
                        <link>
                            <xsl:attribute name='rel'>stylesheet</xsl:attribute>
                            <xsl:attribute name='type'>text/css</xsl:attribute>
                            <xsl:attribute name='href'><xsl:value-of select='$href' /></xsl:attribute>
                        </link>
                    </xsl:if>
                </xsl:for-each>

                <xsl:for-each select='$siteSettings/*/global/rss | $pTemplate/*/rss | //*/rss'>
                    <link>
                        <xsl:attribute name='rel'>alternate</xsl:attribute>
                        <xsl:attribute name='type'>application/rss+xml</xsl:attribute>
                        <xsl:attribute name='href'><xsl:value-of select='@src' /></xsl:attribute>
                    </link>
                </xsl:for-each>

<!--
			<xsl:variable name='systemScripts' select="php:function('juniper_get_scripts')" />
			<xsl:for-each select="$systemScripts/*/script"><xsl:copy-of select='.'/></xsl:for-each>
-->
                <xsl:for-each select="$siteSettings/*/global/script | //*/script | $pTemplate/*/script">
                    <xsl:variable name='location'><xsl:if test='name(..) = "pagetemplate"'>template</xsl:if></xsl:variable>
                    <xsl:variable name='src' select='php:functionString("page_render::resolve_ref", string(@src), string($location), string($SRC/pagedef/@template))' />
                    <xsl:if test='string-length($src) != 0'>
                        <script>
                            <xsl:attribute name='type'>text/<xsl:value-of select='php:functionString("page_render::script_type", string(@src))'/></xsl:attribute>
                            <xsl:attribute name='src' >
                                <xsl:value-of select='php:functionString("page_render::resolve_ref", string(@src), string($location), string($SRC/pagedef/@template))' />
                            </xsl:attribute>
                        </script>
                    </xsl:if>
                </xsl:for-each>

                <xsl:for-each select='$siteSettings/*/global/meta | $pTemplate/*/meta | //*/meta'>
                    <meta>
                        <xsl:attribute name='name'>
                            <xsl:value-of select='@name' />
                        </xsl:attribute>
                        <xsl:attribute name='content'>
                            <xsl:value-of select='@content' />
                        </xsl:attribute>
                    </meta>
                </xsl:for-each>

                <xsl:for-each select='$siteSettings/*/global/header | $pTemplate/*/header | //*/header'>
                    <xsl:copy-of select='node()'/>
                </xsl:for-each>

                <xsl:variable name='gIcon' select='$siteSettings/global/shortcuticon/@icon' />
                <xsl:variable name='gIconImg' select='$siteSettings/global/shortcuticon/@img' />

                <xsl:choose>
                    <xsl:when test='string(/shortcuticon/@icon)!=""'>
                        <link>
                            <xsl:attribute name='rel'>shortcut icon</xsl:attribute>
                            <xsl:attribute name='href'>
                                <xsl:value-of select='php:functionString("page_render::resolve_ref", string(/shortcuticon/@icon))' />
                            </xsl:attribute>
                        </link>
                    </xsl:when>
                    <xsl:when test='string($pTemplate/*/shortcuticon/@icon)!=""'>
                        <link>
                            <xsl:attribute name='rel'>shortcut icon</xsl:attribute>
                            <xsl:attribute name='href'>
                                <xsl:value-of select='php:functionString("page_render::resolve_ref", string($Template/*/shortcuticon/@icon))' />
                            </xsl:attribute>
                        </link>
                    </xsl:when>
                    <xsl:when test='string-length($gIcon)!=0'>
                        <link>
                            <xsl:attribute name='rel'>shortcut icon</xsl:attribute>
                            <xsl:attribute name='href'>
                                <xsl:value-of select='php:functionString("page_render::resolve_ref", string($gIcon))' />
                            </xsl:attribute>
                        </link>
                    </xsl:when>
                </xsl:choose>
                <xsl:choose>
                    <xsl:when test='/shortcuticon/@img!=""'>
                        <link>
                            <xsl:attribute name='rel'>icon</xsl:attribute>
                            <xsl:attribute name='href'>
                                <xsl:value-of select='php:functionString("page_render::resolve_ref", string(/shortcuticon/@img))' />
                            </xsl:attribute>
                            <xsl:attribute name='type'>
                                <xsl:value-of select='php:functionString("page_render::image_format",string(/shortcuticon/@img))' />
                            </xsl:attribute>
                        </link>
                    </xsl:when>
                    <xsl:when test='$pTemplate/*/shortcuticon/@img!=""'>
                        <link>
                            <xsl:attribute name='rel'>icon</xsl:attribute>
                            <xsl:attribute name='href'>
                                <xsl:value-of select='php:functionString("page_render::resolve_ref", string($Template/*/shortcuticon/@img))' />
                            </xsl:attribute>
                            <xsl:attribute name='type'>
                                <xsl:value-of select='php:functionString("page_render::image_format",string($Template/*/shortcuticon/@img))' />
                            </xsl:attribute>
                        </link>
                    </xsl:when>
                    <xsl:when test='$gIconImg!=""'>
                        <link>
                            <xsl:attribute name='rel'>icon</xsl:attribute>
                            <xsl:attribute name='href'>
                                <xsl:value-of select='php:functionString("page_render::resolve_ref", string($PS//global/shortcuticon/@img))' />
                            </xsl:attribute>
                            <xsl:attribute name='type'>
                                <xsl:value-of select='php:functionString("page_render::image_format",string($PS//global/shortcuticon/@img))' />
                            </xsl:attribute>
                        </link>
                    </xsl:when>
                </xsl:choose>
            </head>

            <body>
                <xsl:for-each select='$pTemplate/*/pagebody/*'>
                    <xsl:call-template name="bodytemplate"/>
                </xsl:for-each>
            </body>
        </html>
    </xsl:template>

    <xsl:template name='bodytemplate'>
        <xsl:variable name='N' select='name()' />
        <xsl:variable name='Ck' select='concat(",",name(),",")' />
        <xsl:variable name='HasNodeHandler' select='string-length($N)!=0 and contains($HandledElements, $Ck)' />
        <!-- ((NH=<xsl:value-of select='$HandledElements' />)) -->
        <!-- [Ck=<xsl:value-of select='$N' />, HNH=<xsl:value-of select='$HasNodeHandler' />] -->

        <xsl:choose>
            <xsl:when test='$HasNodeHandler'>
                <xsl:variable name='NodeResult' select='php:function("page_render::handle_element", $N, .)' />

                <xsl:for-each select='$NodeResult'>
                    <xsl:copy>
                        <xsl:for-each select='@*'><xsl:copy-of select='.' /></xsl:for-each>
                        <xsl:for-each select='*'>
                            <xsl:call-template name='bodytemplate' />
                        </xsl:for-each>
                    </xsl:copy>
                </xsl:for-each>
            </xsl:when>

            <xsl:when test='name()="script"'>
                <script>
                    <xsl:for-each select='@*'><xsl:copy-of select='.' /></xsl:for-each>
                    <xsl:text disable-output-escaping='yes'>
// &lt;![CDATA[</xsl:text>
                    <xsl:copy-of select='./text()' />
                    <xsl:text disable-output-escaping='yes'>//]]&gt;
                    </xsl:text>
                </script>
            </xsl:when>

            <xsl:otherwise>
                <xsl:copy>
                    <xsl:for-each select='@*'><xsl:copy-of select='.' /></xsl:for-each>
                    <xsl:for-each select='*|text()'>
                        <xsl:call-template name='bodytemplate' />
                    </xsl:for-each>
                </xsl:copy>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

</xsl:stylesheet>
<?xml version = "1.0" encoding = "UTF-8"?>
<xsl:stylesheet xmlns:xsl = "http://www.w3.org/1999/XSL/Transform" version = "1.0">
    <xsl:output omit-xml-declaration = "yes" indent = "yes" />

    <xsl:template match = "references">
        <html>
            <head>
                <link rel = "stylesheet" type = "text/css" media = "screen"
                    href = "weathermap.css" />

                <title>PHP Weathermap v%VERSION% Configuration Reference</title>
            </head>

            <body>
                <div id = "frame">
                    %NAV IN HERE%

                    <h2><a name = "configref">Configuration Reference</a></h2>

                    <p>This page is automatically compiled, and documents all the
                    configuration directives that are available in PHP Weathermap
                    %VERSION%. </p>

                    <xsl:apply-templates />
                </div>
            </body>
        </html>
    </xsl:template>

    <xsl:template match = "section">
        <h1 id = "s_scope_{@scope}" class = "configsection">
        <xsl:value-of select = "@name" /></h1>

        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match = "textsection">
        <h1 class = "configsection"><xsl:value-of select = "@name" /></h1>

        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match = "freetext">
        <div class = "preamble">
            <xsl:copy-of xmlns:xhtml = "http://www.w3.org/1999/xhtml"
                select = "xhtml:description/*" />
        </div>
    </xsl:template>

    <xsl:template match = "configentry">
        <div class = "referenceentry">
            <h2><a name = "{./anchor}">

            <xsl:value-of select = "./keyword" /></a></h2>

            <xsl:apply-templates select = "definition" />

            <div class = "description">
                <xsl:copy-of xmlns:xhtml = "http://www.w3.org/1999/xhtml"
                    select = "xhtml:description/*" />
            </div>

            <xsl:apply-templates select = "examples" />

            <xsl:apply-templates select = "changes" />
        </div>
    </xsl:template>

    <xsl:template match = "examples">
        <div class = "examples">
            <h3>Examples</h3>

            <xsl:for-each select = "./example">
                <div class = "example">
                    <h5><xsl:value-of select = "./caption" /></h5>

                    <pre>
                                                    <xsl:value-of select="./content"/>
            </pre>
                </div>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template match = "changes">
        <div class = "changes">
            <h3>Change History</h3>

            <dl>
                <xsl:for-each select = "./change">
                    <dt><xsl:value-of select = "@version" /></dt>

                    <dd><xsl:value-of select = "." /></dd>
                </xsl:for-each>
            </dl>
        </div>
    </xsl:template>

    <xsl:template match = "definition">
        <div class = "definition">
            <xsl:apply-templates />
        </div>
    </xsl:template>

    <xsl:template match = "meta">
        <em class = "meta">

        <xsl:value-of select = "." /></em>
    </xsl:template>
</xsl:stylesheet>
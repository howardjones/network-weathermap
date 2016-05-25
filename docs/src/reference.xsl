<?xml version = "1.0" encoding = "UTF-8"?>
<xsl:stylesheet xmlns:xsl = "http://www.w3.org/1999/XSL/Transform" version = "1.0">
    <xsl:output omit-xml-declaration = "yes" indent = "yes" />

    <xsl:template match = "references">
	<xsl:processing-instruction name="php">include 'vars.php'; $PAGE_TITLE='Configuration Reference'; include 'common-page-head.php'; </xsl:processing-instruction>
                    <h2 id= "configref">Configuration Reference</h2>

                    <p>This page is automatically compiled, and documents all the
                    configuration directives that are available in PHP Weathermap
                    <xsl:processing-instruction name="php">echo $WEATHERMAP_VERSION; </xsl:processing-instruction>. </p>

                    <xsl:apply-templates />
	<xsl:processing-instruction name="php">include 'common-page-foot.php'; </xsl:processing-instruction>
    </xsl:template>

    <xsl:template match = "section">
        <h2 id = "s_scope_{@scope}" class = "configsection">
        <xsl:value-of select = "@name" /></h2>

        <xsl:apply-templates />
    </xsl:template>

    <xsl:template match = "textsection">
        <h2 class = "configsection"><xsl:value-of select = "@name" /></h2>

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
            <h3 id="{./anchor}">

            <xsl:value-of select = "./keyword" /></h3>

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
            <h4>Examples</h4>

            <xsl:for-each select = "./example">
                <blockquote class = "example">
                    <small><cite><xsl:value-of select = "./caption" /></cite></small>

                    <pre>
<xsl:value-of select="./content"/>
					</pre>
                </blockquote>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template match = "changes">
        <div class = "changes">
            <h4>Change History</h4>

            <dl class="small">
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

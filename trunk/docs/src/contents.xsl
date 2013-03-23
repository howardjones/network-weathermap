<?xml version = "1.0" encoding = "UTF-8"?>
<xsl:stylesheet xmlns:xsl = "http://www.w3.org/1999/XSL/Transform" version = "1.0">
    <xsl:output omit-xml-declaration = "yes" indent = "yes" />

    <xsl:template match = "references">
        <freetext>
            <description xmlns = "http://www.w3.org/1999/xhtml">
                <div id = "contents">
                    <xsl:apply-templates select = "section" />
                </div>
            </description>
        </freetext>
    </xsl:template>

    <xsl:template match = "section">
        <h4 class = "configsection"><xsl:value-of select = "@name" /></h4>

        <p id = "context_{@scope}">
        <xsl:apply-templates select = "configentry">
		<xsl:sort select="anchor" />
	</xsl:apply-templates></p>
    </xsl:template>

    <xsl:template match = "configentry">
        <a class="tocentry" href = "#{./anchor}">

        <xsl:value-of select = "./keyword" /></a>
    </xsl:template>
</xsl:stylesheet>

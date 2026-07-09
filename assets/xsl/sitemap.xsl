<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9"
  exclude-result-prefixes="sm">

  <xsl:output method="html" encoding="UTF-8" indent="yes" doctype-system="about:legacy-compat"/>

  <xsl:template match="/">
    <html lang="en">
      <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta name="robots" content="noindex, follow"/>
        <xsl:choose>
          <xsl:when test="sm:sitemapindex">
            <title>XML Sitemap Index — Food Calorie Calculator</title>
          </xsl:when>
          <xsl:otherwise>
            <title>XML Sitemap — Food Calorie Calculator</title>
          </xsl:otherwise>
        </xsl:choose>
        <style>
          * { box-sizing: border-box; margin: 0; padding: 0; }

          body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: #f0f4f1;
            color: #1a202c;
            min-height: 100vh;
          }

          .sm-header {
            background: linear-gradient(135deg, #1a7a3f 0%, #28a356 100%);
            color: #fff;
            padding: 2rem 1.5rem;
          }
          .sm-header__inner {
            max-width: 1140px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 1.25rem;
          }
          .sm-header__icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
          }
          .sm-header__title { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.02em; }
          .sm-header__sub { font-size: 0.875rem; opacity: 0.8; margin-top: 0.2rem; }

          .sm-stats { background: #fff; border-bottom: 1px solid #d1e7d8; }
          .sm-stats__inner {
            max-width: 1140px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            gap: 2.5rem;
            flex-wrap: wrap;
          }
          .sm-stat { display: flex; flex-direction: column; }
          .sm-stat__value { font-size: 1.5rem; font-weight: 700; color: #1a7a3f; line-height: 1; }
          .sm-stat__label { font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 0.2rem; }

          .sm-notice { max-width: 1140px; margin: 1.25rem auto 0; padding: 0 1.5rem; }
          .sm-notice__box {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 0.65rem 1rem;
            font-size: 0.8rem;
            color: #065f46;
          }

          .sm-content { max-width: 1140px; margin: 1.25rem auto 2rem; padding: 0 1.5rem; }
          .sm-table-wrap {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 0 0 1px #d1e7d8;
            overflow-x: auto;
          }

          table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
          thead { background: #1a7a3f; }
          th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #fff;
            white-space: nowrap;
          }
          td { padding: 0.55rem 1rem; border-bottom: 1px solid #eef2ee; vertical-align: middle; }
          tr:last-child td { border-bottom: none; }
          tr:nth-child(even) td { background: #f8fbf8; }
          tr:hover td { background: #f0faf2 !important; }

          .sm-url { color: #1a7a3f; text-decoration: none; word-break: break-all; line-height: 1.4; }
          .sm-url:hover { text-decoration: underline; }
          .sm-date { white-space: nowrap; color: #4b5563; font-size: 0.8rem; }

          .sm-freq {
            display: inline-block;
            padding: 0.18rem 0.55rem;
            border-radius: 999px;
            font-size: 0.73rem;
            font-weight: 600;
            white-space: nowrap;
          }
          .sm-freq--weekly  { background: #dbeafe; color: #1e40af; }
          .sm-freq--monthly { background: #fef9c3; color: #854d0e; }
          .sm-freq--yearly  { background: #f3f4f6; color: #374151; }
          .sm-freq--daily   { background: #d1fae5; color: #065f46; }

          .sm-pri { display: flex; align-items: center; gap: 0.5rem; }
          .sm-pri__track {
            width: 60px; height: 5px;
            background: #e5e7eb; border-radius: 3px; overflow: hidden; flex-shrink: 0;
          }
          .sm-pri__fill { height: 100%; border-radius: 3px; background: #1a7a3f; }
          .sm-pri__val { font-size: 0.78rem; font-weight: 600; color: #374151; white-space: nowrap; }

          .sm-index-badge {
            display: inline-flex; align-items: center; gap: 0.35rem;
            background: #ecfdf5; color: #1a7a3f;
            border: 1px solid #a7f3d0;
            border-radius: 6px; padding: 0.25rem 0.6rem;
            font-size: 0.75rem; font-weight: 600; white-space: nowrap;
          }

          .sm-footer { text-align: center; padding: 1.5rem; font-size: 0.78rem; color: #9ca3af; }
          .sm-footer a { color: #1a7a3f; text-decoration: none; }
          .sm-footer a:hover { text-decoration: underline; }

          @media (max-width: 640px) {
            .sm-col-lastmod, .sm-col-freq, .sm-col-pri { display: none; }
            .sm-header__title { font-size: 1.2rem; }
          }
        </style>
      </head>
      <body>

        <!-- Header -->
        <header class="sm-header">
          <div class="sm-header__inner">
            <div class="sm-header__icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="52" height="52">
                <rect width="512" height="512" rx="96" fill="#075B5E"/>
                <text x="256" y="300" text-anchor="middle" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif" font-size="240" font-weight="700" fill="#fff">C</text>
                <circle cx="380" cy="140" r="50" fill="#FF3F33"/>
                <text x="380" y="158" text-anchor="middle" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif" font-size="60" font-weight="700" fill="#fff">k</text>
              </svg>
            </div>
            <div>
              <xsl:choose>
                <xsl:when test="sm:sitemapindex">
                  <div class="sm-header__title">Food Calorie Calculator — XML Sitemap Index</div>
                  <div class="sm-header__sub">foodcaloriecalculator.co.uk · Master index of all sitemap files</div>
                </xsl:when>
                <xsl:otherwise>
                  <div class="sm-header__title">Food Calorie Calculator — XML Sitemap</div>
                  <div class="sm-header__sub">foodcaloriecalculator.co.uk · Complete URL index for search engines</div>
                </xsl:otherwise>
              </xsl:choose>
            </div>
          </div>
        </header>

        <!-- Stats -->
        <div class="sm-stats">
          <div class="sm-stats__inner">
            <xsl:choose>
              <xsl:when test="sm:sitemapindex">
                <div class="sm-stat">
                  <span class="sm-stat__value"><xsl:value-of select="count(sm:sitemapindex/sm:sitemap)"/></span>
                  <span class="sm-stat__label">Sitemap Files</span>
                </div>
              </xsl:when>
              <xsl:otherwise>
                <div class="sm-stat">
                  <span class="sm-stat__value"><xsl:value-of select="count(sm:urlset/sm:url)"/></span>
                  <span class="sm-stat__label">Total URLs</span>
                </div>
                <div class="sm-stat">
                  <span class="sm-stat__value"><xsl:value-of select="count(sm:urlset/sm:url[sm:changefreq='weekly'])"/></span>
                  <span class="sm-stat__label">Weekly</span>
                </div>
                <div class="sm-stat">
                  <span class="sm-stat__value"><xsl:value-of select="count(sm:urlset/sm:url[sm:changefreq='monthly'])"/></span>
                  <span class="sm-stat__label">Monthly</span>
                </div>
                <div class="sm-stat">
                  <span class="sm-stat__value"><xsl:value-of select="count(sm:urlset/sm:url[sm:changefreq='yearly'])"/></span>
                  <span class="sm-stat__label">Yearly</span>
                </div>
              </xsl:otherwise>
            </xsl:choose>
          </div>
        </div>

        <!-- Notice -->
        <div class="sm-notice">
          <p class="sm-notice__box">
            This XML sitemap is used by search engines to discover and index all pages on this website.
            The styling is for human readability only — search engines read the raw XML data.
          </p>
        </div>

        <!-- Table -->
        <div class="sm-content">
          <div class="sm-table-wrap">
            <xsl:choose>

              <!-- SITEMAP INDEX TABLE -->
              <xsl:when test="sm:sitemapindex">
                <table>
                  <thead>
                    <tr>
                      <th>Sitemap File</th>
                      <th class="sm-col-lastmod">Last Modified</th>
                      <th>Type</th>
                    </tr>
                  </thead>
                  <tbody>
                    <xsl:for-each select="sm:sitemapindex/sm:sitemap">
                      <tr>
                        <td>
                          <a class="sm-url" href="{sm:loc}"><xsl:value-of select="sm:loc"/></a>
                        </td>
                        <td class="sm-col-lastmod sm-date">
                          <xsl:value-of select="sm:lastmod"/>
                        </td>
                        <td>
                          <xsl:choose>
                            <xsl:when test="contains(sm:loc, 'page-sitemap')">
                              <span class="sm-index-badge">Pages</span>
                            </xsl:when>
                            <xsl:when test="contains(sm:loc, 'food-category-sitemap')">
                              <span class="sm-index-badge">Categories</span>
                            </xsl:when>
                            <xsl:when test="contains(sm:loc, 'food-sitemap')">
                              <span class="sm-index-badge">Foods</span>
                            </xsl:when>
                            <xsl:otherwise>
                              <span class="sm-index-badge">Sitemap</span>
                            </xsl:otherwise>
                          </xsl:choose>
                        </td>
                      </tr>
                    </xsl:for-each>
                  </tbody>
                </table>
              </xsl:when>

              <!-- URL SET TABLE -->
              <xsl:otherwise>
                <table>
                  <thead>
                    <tr>
                      <th>URL</th>
                      <th class="sm-col-lastmod">Last Modified</th>
                      <th class="sm-col-freq">Change Frequency</th>
                      <th class="sm-col-pri">Priority</th>
                    </tr>
                  </thead>
                  <tbody>
                    <xsl:for-each select="sm:urlset/sm:url">
                      <tr>
                        <td>
                          <a class="sm-url" href="{sm:loc}"><xsl:value-of select="sm:loc"/></a>
                        </td>
                        <td class="sm-col-lastmod sm-date">
                          <xsl:value-of select="sm:lastmod"/>
                        </td>
                        <td class="sm-col-freq">
                          <xsl:variable name="freq" select="sm:changefreq"/>
                          <span>
                            <xsl:attribute name="class">
                              <xsl:choose>
                                <xsl:when test="$freq = 'daily'">sm-freq sm-freq--daily</xsl:when>
                                <xsl:when test="$freq = 'weekly'">sm-freq sm-freq--weekly</xsl:when>
                                <xsl:when test="$freq = 'monthly'">sm-freq sm-freq--monthly</xsl:when>
                                <xsl:otherwise>sm-freq sm-freq--yearly</xsl:otherwise>
                              </xsl:choose>
                            </xsl:attribute>
                            <xsl:value-of select="$freq"/>
                          </span>
                        </td>
                        <td class="sm-col-pri">
                          <div class="sm-pri">
                            <div class="sm-pri__track">
                              <div class="sm-pri__fill">
                                <xsl:attribute name="style">width:<xsl:value-of select="number(sm:priority) * 100"/>%</xsl:attribute>
                              </div>
                            </div>
                            <span class="sm-pri__val"><xsl:value-of select="sm:priority"/></span>
                          </div>
                        </td>
                      </tr>
                    </xsl:for-each>
                  </tbody>
                </table>
              </xsl:otherwise>

            </xsl:choose>
          </div>
        </div>

        <!-- Footer -->
        <div class="sm-footer">
          Generated by <a href="https://foodcaloriecalculator.co.uk">Food Calorie Calculator</a>
          &#xB7; Built by <a href="https://thekhandigital.com">The Khan Digital</a>
        </div>

      </body>
    </html>
  </xsl:template>

</xsl:stylesheet>

import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

const isPages = process.env.GITHUB_PAGES === 'true';

export default defineConfig({
  site: isPages ? 'https://coltigerv2.github.io' : 'https://www.steine-ankauf.de',
  base: isPages ? '/steineankauf-homepage/' : '/',
  integrations: [sitemap()],
});

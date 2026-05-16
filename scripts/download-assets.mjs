import { createWriteStream } from 'node:fs';
import { mkdir, stat } from 'node:fs/promises';
import { pipeline } from 'node:stream/promises';

const assets = [
  {
    name: 'steine.jpg',
    url: 'https://www.dropbox.com/scl/fi/ea1s6cem1g1pjfy42b3k4/steine.jpg?rlkey=9igdra1zmjvnodiygek2t6n17&raw=1',
  },
  {
    name: 'laden.jpg',
    url: 'https://www.dropbox.com/scl/fi/tjwvu8usph8o2vo2jy30a/laden.jpg?rlkey=lctmodid0benc3r9eaw5jbwdl&raw=1',
  },
  {
    name: 'pierre.jpeg',
    url: 'https://www.dropbox.com/scl/fi/quz77oqbcvjp3y1tfbrv1/Pierre.jpeg?rlkey=g2h4ptrrz8m5w0kuiudiinrt3&raw=1',
  },
];

const outputDir = new URL('../public/assets/photos/', import.meta.url);
await mkdir(outputDir, { recursive: true });

async function exists(fileUrl) {
  try {
    const info = await stat(fileUrl);
    return info.isFile() && info.size > 0;
  } catch {
    return false;
  }
}

for (const asset of assets) {
  const outputFile = new URL(asset.name, outputDir);

  if (await exists(outputFile)) {
    console.log(`[assets] ${asset.name} already exists, skipping`);
    continue;
  }

  console.log(`[assets] downloading ${asset.name}`);
  const response = await fetch(asset.url, { redirect: 'follow' });

  if (!response.ok || !response.body) {
    throw new Error(`Could not download ${asset.name}: ${response.status} ${response.statusText}`);
  }

  await pipeline(response.body, createWriteStream(outputFile));
  console.log(`[assets] saved ${asset.name}`);
}

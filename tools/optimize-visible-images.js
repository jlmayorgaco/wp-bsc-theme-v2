const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const rootDir = process.cwd();

const targets = [
  {
    file: 'images/product-category/PORTADA_SKINCARE_INTERNA.jpg',
    widths: [1280, 1920, 2560],
  },
  {
    file: 'images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
    widths: [480, 720, 960, 1280],
  },
  {
    file: 'images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
    widths: [480, 720, 960, 1280],
  },
  {
    file: 'images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
    widths: [480, 720, 960, 1280],
  },
  {
    file: 'images/shop/4PAG_INTERNAR_IMAGENES_WEB.jpg',
    widths: [480, 720, 960, 1280],
  },
  {
    file: 'images/shop/5PAG_INTERNAR_IMAGENES_WEB.jpg',
    widths: [480, 720, 960, 1280],
  },
  {
    file: 'images/shop/6PAG_INTERNAR_IMAGENES_WEB.jpg',
    widths: [480, 720, 960, 1280],
  },
  {
    file: 'images/signin_signup/bsc_signin_cover.png',
    widths: [480, 768, 1024, 1280],
  },
  {
    file: 'images/signin_signup/bsc_signup_cover.png',
    widths: [480, 768, 1024, 1280],
  },
  {
    file: 'images/home_brands/bsc_home_brands_bg.jpg',
    widths: [768, 1280, 1920],
  },
  {
    file: 'images/bsc_home_about_bg.jpg',
    widths: [768, 1280, 1920],
  },
];

function variantPath(file, width, extension) {
  const parsed = path.parse(file);
  return path.join(parsed.dir, `${parsed.name}-${width}w.${extension}`);
}

async function optimizeTarget(target) {
  const absoluteSource = path.join(rootDir, target.file);

  if (!fs.existsSync(absoluteSource)) {
    throw new Error(`Missing source image: ${target.file}`);
  }

  const metadata = await sharp(absoluteSource).metadata();
  const sourceWidth = metadata.width || 0;
  const widths = target.widths.filter((width) => width <= sourceWidth);

  for (const width of widths) {
    const webpFile = path.join(rootDir, variantPath(target.file, width, 'webp'));
    const avifFile = path.join(rootDir, variantPath(target.file, width, 'avif'));

    await sharp(absoluteSource)
      .resize({ width, withoutEnlargement: true })
      .webp({ quality: 78, effort: 5 })
      .toFile(webpFile);

    await sharp(absoluteSource)
      .resize({ width, withoutEnlargement: true })
      .avif({ quality: 48, effort: 6 })
      .toFile(avifFile);
  }

  return {
    source: target.file,
    originalKb: Math.round(fs.statSync(absoluteSource).size / 1024),
    variants: widths.length * 2,
  };
}

(async () => {
  const results = [];

  for (const target of targets) {
    results.push(await optimizeTarget(target));
  }

  for (const result of results) {
    console.log(`${result.source}: ${result.originalKb} KB source, ${result.variants} responsive variants`);
  }
})().catch((error) => {
  console.error(error.message);
  process.exit(1);
});

# FurniSync Ecommerce Promos

Three 60-second Remotion videos focused on the FurniSync customer ecommerce experience.

- `FurniSyncEcommerce` uses generated interface mockups.
- `FurniSyncImagePromo` animates the supplied FurniSync logo and mobile screenshots.
- `FurniSyncVerticalPromo` recreates the interface as independently animated elements in a 1080x1920 portrait composition.

## Preview

```powershell
cd backend/promo/ecommerce
pnpm install
pnpm start
```

Select either composition in Remotion Studio.

## Render

```powershell
pnpm render
```

The MP4 is written to `out/furnisync-ecommerce-promo.mp4`.

To render the supplied-image version:

```powershell
pnpm render:images
```

The MP4 is written to `out/furnisync-ecommerce-image-promo.mp4`.

To render the portrait animated-interface version:

```powershell
pnpm render:vertical
```

The MP4 is written to `out/furnisync-ecommerce-vertical-promo.mp4`.

## Structure

- 0–6s: brand opening and value proposition
- 6–16s: browse categories and live product availability
- 16–27s: interactive 3D product exploration
- 27–37s: product comparison
- 37–48s: streamlined checkout
- 48–55s: order tracking
- 55–60s: closing call to action

The image-based composition uses the files in `public/assets/` and does not require live application data.

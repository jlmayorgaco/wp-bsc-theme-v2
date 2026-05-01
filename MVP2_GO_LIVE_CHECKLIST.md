# MVP2 Go-Live Checklist

Date: 2026-04-30
Branch: `MVP2`

## 1. Pre-Flight
- Confirm working tree is clean except explicitly ignored local files.
- Confirm target deploy artifact is this branch revision.
- Confirm database backup exists and is restorable.
- Confirm theme zip or server-side theme snapshot exists.
- Confirm WooCommerce status page shows no active fatal issues.
- Confirm payment gateway credentials and shipping rules match production.
- Confirm admin operator has access to:
  - Pedidos BSC
  - Productos BSC
  - Informes BSC
  - Showcase
  - Bubble Points

## 2. Required Gates Before GO
- `npm run test:e2e:smoke`
- `npm run test:e2e:visual`
- `npm run lint`
- Verify `MVP2_RELEASE_STATUS.md` is current.
- Manual homepage review on mobile / tablet / desktop.
- Manual checkout walk-through with seeded cart.
- Manual cart stress check: rapid +/- taps, decrement to zero, duplicate product rows or variants, and cart badge sync.
- Manual admin order popup check:
  - packing view
  - stock source deduction from bodega
  - stock source deduction from showroom
  - labels / PDF view

## 3. GO / NO-GO Decision
Release is `GO` only if all of these are true:
- smoke suite is green
- visual suite is green
- Home, checkout, account, Bubble Points, and admin orders were spot-checked manually
- no unexpected content drift is visible in updated baselines
- backup exists

Release is `NO-GO` if any of these happen:
- checkout smoke fails
- account / order detail smoke fails
- visual diff shows unreviewed UI drift
- admin order or product screens do not load
- post-deploy payment or shipping flow is broken

## 4. Deploy Sequence
1. Put team on release window.
2. Capture DB backup.
3. Capture current theme backup.
4. Deploy `MVP2` artifact.
5. Clear caches if the environment uses them.
6. Open storefront and admin in a fresh session.
7. Run the post-deploy smoke below.

## 5. Post-Deploy Smoke
### Storefront
- Home loads and header renders correctly.
- Category page loads product cards.
- PDP opens from category.
- Add-to-cart toggles quantity controls and restores CTA at zero.
- Checkout renders with cart prepared.
- Checkout cart +/- reconciles quantity, row total, and floating cart badge after each update.
- Contact page form shell works.
- Bubble Creators form shell works.

### Auth / Account
- Login page loads.
- Register page loads.
- My Account loads.
- View Order loads.
- Thank You route loads for a known order.

### Admin
- `BSC > Pedidos` loads.
- Packing popup opens.
- Packing popup can deduct one line item from Bodega and another from Showroom, then prevents double deduction on reload.
- Labels / PDF popup opens.
- `BSC > Productos` list and edit page load.
- `BSC > Informes` sales and stock tabs load.
- `BSC > Showcase` loads and search works.
- `BSC > Bubble Points` loads.

### Emails
- Preview pages load for:
  - welcome
  - password reset
  - birthday
  - order confirmed
  - order preparing
  - order shipped
  - order delivered
  - order cancelled
  - followup inactive
  - followup repurchase

## 6. Rollback Pack
If release fails after deploy:
1. Stop further manual operations in admin.
2. Revert to previous theme artifact or previous server snapshot.
3. Restore database only if the failure involved destructive data changes.
4. Clear caches again.
5. Re-run storefront smoke on:
   - Home
   - category
   - PDP
   - checkout
   - account
6. Re-run admin smoke on:
   - Pedidos
   - Productos
   - Informes

## 7. Notes
- `Videos/` is unrelated local material and not part of release scope.
- `cicd/deploy.php` is intentionally excluded from the current execution scope by instruction.

# Retired React deployment guide

The current site uses Blade/Livewire and Filament. Use [the native deployment runbook](../DEPLOY_CPANEL.md) and [production readiness notes](migration/step-14-deployment-readiness.md).

The former split-domain React instructions are obsolete for the native migration. Publishing the retained React build over the Laravel document root would bypass native routes. Historical instructions remain available in Git history. Preserve the existing APP_KEY and keep the application outside its public document root.

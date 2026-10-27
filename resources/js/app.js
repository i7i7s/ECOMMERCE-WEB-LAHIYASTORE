import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import caseEditor from './case-editor';
import { registerConfirmDialog } from './confirm-dialog';
import { registerConnectionHandling, registerOfflinePage } from './connection';
import policyGuide from './policy-guide';
import { initTearReveal } from './tear-reveal';

Alpine.data('caseEditor', caseEditor);
Alpine.data('policyGuide', policyGuide);
registerConfirmDialog(Alpine);
registerConnectionHandling(Alpine, Livewire);
registerOfflinePage();

document.addEventListener('DOMContentLoaded', initTearReveal);
document.addEventListener('livewire:navigated', initTearReveal);

Livewire.start();

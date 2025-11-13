import { Routes } from '@angular/router';
import { QrGeneratorComponent } from './qr-generator/qr-generator.component';

export const routes: Routes = [
    {
        path: '',
        component: QrGeneratorComponent,
    },
    {
        path: '**',
        redirectTo: '',
    },
];

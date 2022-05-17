import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {PageNotFoundComponent} from "./_components/page-not-found/page-not-found.component";
import {LoginGuard} from "./_guards/login.guard";
import {RedirectIfLoggedInGuard} from "./_guards/redirect-if-logged-in.guard";
import {NoAccessComponent} from "./_components/no-access/no-access.component";
import {RedirectIfSetupDoneGuard} from "./_guards/redirect-if-setup-done.guard";

const routes: Routes = [
  {
    path: 'setup',
    loadChildren: () => import('./_views/setup/setup.module').then(mod => mod.SetupModule),
    canLoad: [RedirectIfSetupDoneGuard]
  },
  {
    path: 'login',
    loadChildren: () => import('./_views/login/login.module').then(mod => mod.LoginModule),
    canLoad: [RedirectIfLoggedInGuard]
  },
  {
    path: '',
    loadChildren: () => import('./_views/restricted/restricted.module').then(mod => mod.RestrictedModule),
    canLoad: [LoginGuard]
  },
  { path: '404', component: PageNotFoundComponent},
  { path: 'no-access', component: NoAccessComponent},
  { path: '**', redirectTo: '404', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes, {useHash: true})],
  exports: [RouterModule]
})
export class AppRoutingModule { }

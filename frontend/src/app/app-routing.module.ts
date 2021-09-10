import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {PageNotFoundComponent} from "./_components/page-not-found/page-not-found.component";
import {LoginGuard} from "./_guards/login.guard";
import {RedirectIfLoggedInGuard} from "./_guards/redirect-if-logged-in.guard";
import {NoAccessComponent} from "./_components/no-access/no-access.component";
import {RedirectIfSetupDoneGuard} from "./_guards/redirect-if-setup-done.guard";

const routes: Routes = [
  {
    path: 'login',
    loadChildren: () => import('./_views/login/login.module').then(mod => mod.LoginModule),
    canLoad: [RedirectIfLoggedInGuard],
    canActivate: [RedirectIfLoggedInGuard]
  },
  {
    path: 'setup',
    loadChildren: () => import('./_views/setup/setup.module').then(mod => mod.SetupModule),
    canLoad: [RedirectIfSetupDoneGuard],
    canActivate: [RedirectIfSetupDoneGuard]
  },
  {
    path: 'main',
    loadChildren: () => import('./_views/main/main.module').then(mod => mod.MainModule),
    canLoad: [LoginGuard],
    canActivate: [LoginGuard]
  },
  {
    path: 'courses',
    loadChildren: () => import('./_views/courses/courses.module').then(mod => mod.CoursesModule),
    canLoad: [LoginGuard],
    canActivate: [LoginGuard]
  },
  {
    path: 'users',
    loadChildren: () => import('./_views/users/users.module').then(mod => mod.UsersModule),
    canLoad: [LoginGuard],
    canActivate: [LoginGuard]
  },
  {
    path: 'settings',
    loadChildren: () => import('./_views/settings/settings.module').then(mod => mod.SettingsModule),
    canLoad: [LoginGuard],
    canActivate: [LoginGuard]
  },
  { path: '', redirectTo: 'main', pathMatch: 'full' },
  { path: '404', component: PageNotFoundComponent},
  { path: 'no-access', component: NoAccessComponent},
  { path: '**', redirectTo: '404', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }

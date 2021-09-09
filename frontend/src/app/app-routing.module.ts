import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {PageNotFoundComponent} from "./_components/page-not-found/page-not-found.component";
import {SetupGuard} from "./_guards/setup.guard";

const routes: Routes = [
  {
    path: 'login',
    loadChildren: () => import('./_views/login/login.module').then(mod => mod.LoginModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  {
    path: 'setup',
    loadChildren: () => import('./_views/setup/setup.module').then(mod => mod.SetupModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  {
    path: 'main',
    loadChildren: () => import('./_views/main/main.module').then(mod => mod.MainModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  {
    path: 'courses',
    loadChildren: () => import('./_views/courses/courses.module').then(mod => mod.CoursesModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  {
    path: 'users',
    loadChildren: () => import('./_views/users/users.module').then(mod => mod.UsersModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  {
    path: 'settings',
    loadChildren: () => import('./_views/settings/settings.module').then(mod => mod.SettingsModule),
    canLoad: [SetupGuard],
    canActivate: [SetupGuard]
  },
  { path: '', redirectTo: 'main', pathMatch: 'full' },
  { path: '404', component: PageNotFoundComponent},
  { path: '**', redirectTo: '404', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }

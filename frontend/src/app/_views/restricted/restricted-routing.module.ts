import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { AdminGuard } from "../../_guards/admin.guard";
import { DocsGuard } from "../../_guards/docs-guard.service";

import { RestrictedComponent } from "./restricted.component";
import { ComingSoonComponent } from "../../_components/misc/pages/coming-soon/coming-soon.component";

const routes: Routes = [
  {
    path: '',
    component: RestrictedComponent ,
    children: [
      {
        path: 'home',
        loadChildren: () => import('./home/home.module').then(mod => mod.HomeModule)
      },
      {
        path: 'profile/:id',
        loadChildren: () => import('./profile/profile.module').then(mod => mod.ProfileModule)
      },
      {
        path: 'courses',
        loadChildren: () => import('./courses/courses.module').then(mod => mod.CoursesModule),
      },
      {
        path: 'users',
        loadChildren: () => import('./users/users.module').then(mod => mod.UsersModule),
        canActivate: [AdminGuard]
      },
      {
        path: 'settings',
        loadChildren: () => import('./settings/settings.module').then(mod => mod.SettingsModule),
        canActivate: [AdminGuard]
      },
      {
        path: 'about',
        component: ComingSoonComponent
        // component: AboutComponent FIXME: do about
      },
      {
        path: 'docs',
        component: ComingSoonComponent,
        // loadChildren: () => import('./docs/docs.module').then(mod => mod.DocsModule), FIXME: refactor docs
        canActivate: [DocsGuard]
      },
      { path: '', redirectTo: 'home', pathMatch: 'full' }
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class RestrictedRoutingModule { }

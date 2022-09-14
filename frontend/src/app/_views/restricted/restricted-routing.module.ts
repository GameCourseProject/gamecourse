import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {AdminGuard} from "../../_guards/admin.guard";
import {RestrictedComponent} from "./restricted.component";
import {AboutComponent} from "./about/about.component";

const routes: Routes = [
  {
    path: '',
    component: RestrictedComponent ,
    children: [
      {
        path: 'main',
        loadChildren: () => import('./main/main.module').then(mod => mod.MainModule)
      },
      {
        path: 'profile',
        loadChildren: () => import('./my-info/my-info.module').then(mod => mod.MyInfoModule)
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
        component: AboutComponent
      },
      {
        path: 'docs',
        loadChildren: () => import('./docs/docs.module').then(mod => mod.DocsModule)
      },
      { path: '', redirectTo: 'main', pathMatch: 'full' }
    ]
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class RestrictedRoutingModule { }

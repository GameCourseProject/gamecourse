import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import {ModulesComponent} from "./modules/modules.component";
import {ComingSoonComponent} from "../../../_components/misc/pages/coming-soon/coming-soon.component";
import {BeginComponent} from "./begin/begin.component";
import {ViewEditorComponent} from "./view-editor/view-editor.component";
import {ElComponent} from "./el/el.component";

const routes: Routes = [
  {
    path: '',
    children: [
      {
        path: 'introduction',
        component: BeginComponent
      },
      {
        path: 'modules',
        component: ModulesComponent
      },
      {
        path: 'el',
        component: ElComponent
      },
      {
        path: 'rule-editor',
        component: ComingSoonComponent
      },
      {
        path: 'view-editor',
        component: ViewEditorComponent
      },
    ],
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class DocsRoutingModule { }

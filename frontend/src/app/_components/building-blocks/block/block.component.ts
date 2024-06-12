import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {BlockDirection, ViewBlock} from "../../../_domain/views/view-types/view-block";
import {View, ViewMode} from "../../../_domain/views/view";
import {CdkDragDrop, moveItemInArray, transferArrayItem} from '@angular/cdk/drag-drop';
import { groupedChildren } from 'src/app/_domain/views/build-view-tree/build-view-tree';
import {HistoryService} from "../../../_services/history.service";
import {ViewEditorService} from "../../../_services/view-editor.service";
import * as _ from "lodash";
import {ViewSelectionService} from "../../../_services/view-selection.service";
import {ModalService} from "../../../_services/modal.service";
import {installPatch} from "./nested-drag-drop-patch";
import {ViewCollapse} from "../../../_domain/views/view-types/view-collapse";

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html',
  styleUrls: ['./block.component.scss']
})
export class BBBlockComponent implements OnInit {

  @Input() view: ViewBlock;
  @Output() addComponentEvent = new EventEmitter<void>();

  classes: string;
  children: string;

  constructor(
    public selection: ViewSelectionService,
    public service: ViewEditorService,
    public history: HistoryService
  ) { }

  ngOnInit(): void {
    installPatch();

    this.classes = 'bb-block bb-block-' + this.view.direction;
    if (this.view.columns) this.classes += ' bb-block-cols-' + this.view.columns;
    if (this.view.responsive) this.classes += ' bb-block-responsive'
    this.children = 'bb-block-children';

    // Include certain classes on parent
    if (this.view.classList) {
      for (const cl of this.view.classList.split(' ')) {
        if (cl.startsWith('rounded')) this.classes += ' ' + cl;
        if (cl === 'flex-wrap') this.classes += ' bb-block-wrap';
      }
    }
  }

  /*** ------------------------------------------------ ***/
  /*** ----------------- Drag and Drop ---------------- ***/
  /*** ------------------------------------------------ ***/

  drop (event: CdkDragDrop<View[]>) {
    if (event.previousContainer === event.container && event.previousIndex != event.currentIndex) {
      moveItemInArray(
        event.container.data,
        event.previousIndex,
        event.currentIndex
      );

      const group = groupedChildren.get(+event.container.id);
      moveItemInArray(group, event.previousIndex, event.currentIndex);

      this.history.saveState({
        viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
        groupedChildren: groupedChildren
      });

    } else if (event.previousContainer !== event.container) {
      // TODO: Aspects
      transferArrayItem(
        event.previousContainer.data,
        event.container.data,
        event.previousIndex,
        event.currentIndex
      );

      const prevGroup = groupedChildren.get(+event.previousContainer.id);

      let newGroup = groupedChildren.get(+event.container.id);
      if (!newGroup) {
        newGroup = [];
        groupedChildren.set(+event.container.id, newGroup);
      }

      transferArrayItem(prevGroup, newGroup, event.previousIndex, event.currentIndex);
      this.view.findView(newGroup[event.currentIndex][0]).parent = this.view;

      this.history.saveState({
        viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
        groupedChildren: groupedChildren
      });
    }
  }

  private getIdsRecursive(item: any): string[] {
    let ids = [];
    if (item instanceof ViewCollapse) {
      ids = ids.concat(this.getIdsRecursive(item.content));
      ids = ids.concat(this.getIdsRecursive(item.header));
    }
    else if ('children' in item) {
      ids.push(String(item.id));
      item.children.forEach(childItem => {
        ids = ids.concat(this.getIdsRecursive(childItem));
      });
    }
    return ids;
  }

  public get connectedTo(): string[] {
    let view: View = this.view;
    while (view.parent) {
      view = view.parent;
    }
    return this.getIdsRecursive(view).reverse();
  }

  getCantDrag(): boolean {
    return ModalService.isAnyOpen();
  }


  /*** ------------------------------------------------ ***/
  /*** -------------------- Helpers ------------------- ***/
  /*** ------------------------------------------------ ***/

  addComponent(event: any): void {
    event.stopPropagation();
    this.selection.set(this.view);
    this.addComponentEvent.emit();
  }

  get ViewMode(): typeof ViewMode {
    return ViewMode;
  }

  protected readonly String = String;
  protected readonly BlockDirection = BlockDirection;
}

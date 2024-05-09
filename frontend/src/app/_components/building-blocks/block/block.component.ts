import {Component, Input, OnInit, QueryList, ViewChildren} from '@angular/core';
import { asapScheduler } from 'rxjs';
import {ViewBlock} from "../../../_domain/views/view-types/view-block";
import {ViewMode} from "../../../_domain/views/view";
import {
  CdkDragDrop,
  CdkDrag,
  CdkDropList,
  moveItemInArray,
  transferArrayItem,
  CdkDragEnter,
  CdkDragExit,
  CdkDragStart,
  DragRef,
  DropListRef,
} from '@angular/cdk/drag-drop';
import { groupedChildren } from 'src/app/_domain/views/build-view-tree/build-view-tree';
import {HistoryService} from "../../../_services/history.service";
import {ViewEditorService} from "../../../_services/view-editor.service";
import * as _ from "lodash";
import {ModalService} from "../../../_services/modal.service";
import {ViewSelectionService} from "../../../_services/view-selection.service";

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html',
  styleUrls: ['./block.component.scss']
})
export class BBBlockComponent implements OnInit {

  @Input() view: ViewBlock;

  classes: string;
  children: string;

  @ViewChildren(CdkDropList)
  private dlq: QueryList<CdkDropList>;

  public dls: CdkDropList[] = [];

  constructor(
    public selection: ViewSelectionService,
    public service: ViewEditorService,
    public history: HistoryService
  ) { }

  ngOnInit(): void {
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

  ngAfterViewInit() {
    let ldls: CdkDropList[] = [];

    this.dlq.forEach((dl) => {
      console.log('found DropList ' + dl.id);
      ldls.push(dl);
    });

    ldls = ldls.reverse();

    asapScheduler.schedule(() => {
      this.dls = ldls;

      // one array of siblings (shared for a whole tree)
      const siblings = this.dls.map((dl) => dl?._dropListRef);
      // overwrite _getSiblingContainerFromPosition method
      this.dlq.forEach((dl) => {
        dl._dropListRef._getSiblingContainerFromPosition = (item, x, y) =>
          siblings.find((sibling) => sibling._canReceive(item, x, y));
      });
    });
  }

  drop(event: any) { //CdkDragDrop<?>
    if (event.previousContainer === event.container) {
      moveItemInArray(
        event.container.data,
        event.previousIndex,
        event.currentIndex
      );
      const group = groupedChildren.get(this.view.id);
      moveItemInArray(group, event.previousIndex, event.currentIndex);

      if (event.previousIndex != event.currentIndex) {
        this.history.saveState({
          viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
          groupedChildren: groupedChildren
        });
      }

    } else {
      transferArrayItem(
        event.previousContainer.data,
        event.container.data,
        event.previousIndex,
        event.currentIndex
      );
      // TODO: move in grouped children
    }
  }

  get ViewMode(): typeof ViewMode {
    return ViewMode;
  }

  get orientation(): "horizontal" | "vertical" {
    return this.view.direction as "horizontal" | "vertical";
  }

  get cantDrag(): boolean {
    return ModalService.isOpen("component-editor");
  }
}

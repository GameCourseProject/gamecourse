import {Injectable} from '@angular/core';
import {Aspect} from '../_domain/views/aspects/aspect';
import {View, ViewMode} from '../_domain/views/view';
import * as _ from "lodash"
import {
  addToGroupedChildren,
  addVariantToGroupedChildren,
  getFakeId,
  groupedChildren,
  viewsDeleted
} from '../_domain/views/build-view-tree/build-view-tree';
import {Subject} from 'rxjs';
import {ViewTable} from '../_domain/views/view-types/view-table';
import {buildView} from "../_domain/views/build-view/build-view";
import {ViewBlock} from "../_domain/views/view-types/view-block";
import {ViewCollapse} from "../_domain/views/view-types/view-collapse";


/**
 * This service can keep information about and manipulate the
 * several views/aspects of a page/template
 *
 * IMPORTANT NOTE: the edit action is inside the component-editor
 * component, not here, due to its complexity
 */
@Injectable({
  providedIn: 'root'
})
export class ViewEditorService {
  public viewsByAspect: { aspect: Aspect, view: View | null }[];
  public selectedAspect: Aspect;  // Selected aspect for previewing and editing
  public rolesHierarchy;
  public aspectsToDelete: Aspect[] = [];
  public aspectsToAdd: { newAspect: Aspect, viewToCopy: View }[] = [];
  public aspectsToChange: { old: Aspect, newAspect: Aspect }[] = [];

  selectedChange: Subject<View> = new Subject<View>();

  constructor() { }

  getSelectedView(): View {
    return this.viewsByAspect.find((e) => _.isEqual(e.aspect, this.selectedAspect))?.view;
  }

  /*********************************** ASPECTS MANAGEMENT  ***********************************/

  getEntryOfAspect(aspect: Aspect) {
    return this.viewsByAspect.find((e) => _.isEqual(e.aspect, aspect));
  }

  isMoreSpecific(role: string | null, ancestor: string | null): boolean {
    if (role && !ancestor || role === ancestor) {
      return true;
    }
    else if (this.rolesHierarchy[role]?.parent) {
      return this.isMoreSpecific(this.rolesHierarchy[role].parent._name, ancestor);
    }
    else {
      return false;
    }
  }

  deleteAspect(aspect: Aspect) {
    const oldEntry = this.getEntryOfAspect(aspect);
    this.viewsByAspect = this.viewsByAspect.filter(e => !_.isEqual(e, oldEntry));

    this.viewsByAspect.forEach(e => {
      e.view?.modifyAspect([aspect], e.aspect);
    });

    this.recursivelyAddToViewsDeleted(oldEntry.view);
  }
  recursivelyAddToViewsDeleted(item: View) {
    // View doesn't exist anymore in any tree -> delete from database
    if (item.id > 0 && this.viewsByAspect.filter((e) => e.view?.findView(item.id)).length <= 0) {
      viewsDeleted.push(item.id);
      return; // adding the parent is enough, backend will delete all dependants too
    }
    else if (item instanceof ViewBlock) {
      for (let child of item.children) {
        this.recursivelyAddToViewsDeleted(child);
      }
    }
    else if (item instanceof ViewCollapse) {
      this.recursivelyAddToViewsDeleted(item.header);
      this.recursivelyAddToViewsDeleted(item.content);
    }
    else if (item instanceof ViewTable) {
      for (let row of item.headerRows) {
        this.recursivelyAddToViewsDeleted(row);
      }
      for (let row of item.bodyRows) {
        this.recursivelyAddToViewsDeleted(row);
      }
    }
  }

  createAspect(aspect: Aspect, view: View) {
    this.viewsByAspect.push({ aspect: aspect, view: view });
  }

  changeAspect(old: Aspect, newAspect: Aspect) {
    // aspects lower in hierarchy and the aspect itself
    const defaultAspect = new Aspect(null, null)
    const aspectsToReplace = this.viewsByAspect.filter((e) => {
      if (_.isEqual(old, defaultAspect)) return true;
      else if (e.aspect.userRole === old.userRole) return this.isMoreSpecific(old.viewerRole, e.aspect.viewerRole);
      else if (e.aspect.viewerRole === old.viewerRole) return this.isMoreSpecific(old.userRole, e.aspect.userRole);
      else return this.isMoreSpecific(old.viewerRole, e.aspect.viewerRole) && this.isMoreSpecific(old.userRole, e.aspect.userRole)
    }).map(e => e.aspect);

    this.viewsByAspect = this.viewsByAspect.map(e => {
      if (_.isEqual(e.aspect, old)) {
        e.view?.modifyAspect(aspectsToReplace, newAspect);
        return {aspect: newAspect, view: e.view}
      }
      else return e
    })
  }

  applyAspectChanges() {
    // Delete aspects
    for (let deleted of this.aspectsToDelete) {
      this.deleteAspect(deleted);
    }
    this.aspectsToDelete = [];

    // Modify roles of existing aspects
    for (let changed of this.aspectsToChange) {
      this.changeAspect(changed.old, changed.newAspect);
      if (_.isEqual(this.selectedAspect, changed.old)) {
        this.selectedAspect = changed.newAspect;
      }
    }
    this.aspectsToChange = [];

    // Create new aspects
    for (let aspect of this.aspectsToAdd) {
      const view = _.cloneDeep(aspect.viewToCopy);

      const defaultAspect = new Aspect(null, null)
      const aspectsToReplace = this.viewsByAspect.filter((e) => {
        if (_.isEqual(e.aspect, defaultAspect)) return false;
        if (e.aspect.userRole === aspect.newAspect.userRole) return !this.isMoreSpecific(aspect.newAspect.viewerRole, e.aspect.viewerRole);
        else if (e.aspect.viewerRole === aspect.newAspect.viewerRole) return !this.isMoreSpecific(aspect.newAspect.userRole, e.aspect.userRole);
        else return !this.isMoreSpecific(aspect.newAspect.viewerRole, e.aspect.viewerRole) && !this.isMoreSpecific(aspect.newAspect.userRole, e.aspect.userRole)
      }).map(e => e.aspect);

      view?.modifyAspect(aspectsToReplace, aspect.newAspect);
      this.createAspect(aspect.newAspect, view);
    }
    this.aspectsToAdd = [];
  }

  higherInHierarchy(item: View) {
    const viewsWithThis = this.viewsByAspect.filter((e) => !_.isEqual(this.selectedAspect, e.aspect) && e.view?.findView(item.id));

    const higherInHierarchy = viewsWithThis.filter((e) =>
      (e.aspect.userRole === this.selectedAspect.userRole && this.isMoreSpecific(this.selectedAspect.viewerRole, e.aspect.viewerRole))
      || (e.aspect.userRole !== this.selectedAspect.userRole && this.isMoreSpecific(this.selectedAspect.userRole, e.aspect.userRole))
    );

    return higherInHierarchy;
  }

  getFutureAspects(): Aspect[] {
    // deleted
    const futureAspects = this.viewsByAspect.filter(e => this.aspectsToDelete.findIndex(toDel => _.isEqual(e.aspect, toDel)) == -1).map(e => e.aspect);
    // changed
    for (let changed of this.aspectsToChange) {
      const found = futureAspects.findIndex(e => _.isEqual(e, changed.old));
      if (found != -1) futureAspects[found] = changed.newAspect;
    }
    // added
    for (let added of this.aspectsToAdd) {
      futureAspects.push(added.newAspect);
    }
    return futureAspects;
  }


  /*********************************** VIEWS ACTIONS  ***********************************/

  /*
   * Adds a view to another view
   * Option to add it by reference or to create a copy (by value)
   * If to is null, it adds it as root
   */
  add(item: View, to: View | null, mode: "value" | "ref" = "ref") {
    // All Aspects that should display the new item (this one and all others beneath in hierarchy)
    const toAdd = this.viewsByAspect.filter((e) =>
      (e.aspect.userRole === this.selectedAspect.userRole && this.isMoreSpecific(e.aspect.viewerRole, this.selectedAspect.viewerRole))
      || (e.aspect.userRole !== this.selectedAspect.userRole && this.isMoreSpecific(e.aspect.userRole, this.selectedAspect.userRole))
    );

    let newItem;
    if (item instanceof ViewTable) {
      newItem = new ViewTable(ViewMode.EDIT, item.id, item.viewRoot, null, this.selectedAspect, item.footers, item.searching,
        item.columnFiltering, item.paging, item.lengthChange, item.info, item.ordering, item.orderingBy,
        item.headerRows.concat(item.bodyRows), item.cssId, item.classList, item.styles, item.visibilityType,
        item.visibilityCondition, item.loopData, item.variables, item.events);
    }
    else {
      newItem = _.cloneDeep(item);
      newItem.aspect = this.selectedAspect;
    }
    newItem.switchMode(ViewMode.EDIT);

    if (mode === "value") newItem.replaceWithFakeIds();

    // Add to a view
    if (to) {
      for (let el of toAdd) {
        let itemToAdd = _.cloneDeep(newItem);
        el.view?.findView(to.id)?.addChildViewToViewTree(itemToAdd);
      }
      addToGroupedChildren(newItem, to.id);
    }
    // Add as root
    else {
      this.viewsByAspect = this.viewsByAspect.map(e => {
        if (toAdd.includes(e)) {
          let itemToAdd = _.cloneDeep(newItem);
          return { aspect: e.aspect, view: itemToAdd };
        }
        else return e
      })
      addToGroupedChildren(newItem, null);
      this.selectedChange.next(this.getSelectedView());
    }
  }

  /*
   * Deletes a view
   */
  delete(item: View) {
    const viewsWithThis = this.viewsByAspect.filter((e) => !_.isEqual(this.selectedAspect, e.aspect) && e.view?.findView(item.id));

    const lowerInHierarchy = viewsWithThis.filter((e) =>
      (e.aspect.userRole === this.selectedAspect.userRole && this.isMoreSpecific(e.aspect.viewerRole, this.selectedAspect.viewerRole))
      || (e.aspect.userRole !== this.selectedAspect.userRole && this.isMoreSpecific(e.aspect.userRole, this.selectedAspect.userRole))
    );
    lowerInHierarchy.push(this.getEntryOfAspect(this.selectedAspect));

    const higherInHierarchy = viewsWithThis.filter((e) =>
      (e.aspect.userRole === this.selectedAspect.userRole && this.isMoreSpecific(this.selectedAspect.viewerRole, e.aspect.viewerRole))
      || (e.aspect.userRole !== this.selectedAspect.userRole && this.isMoreSpecific(this.selectedAspect.userRole, e.aspect.userRole))
    );

    // if there is any aspect above, we need to create a new version of the parent, without the item, for this aspect
    if (higherInHierarchy.length > 0 && item.parent) {
      const newBlock = _.cloneDeep(item.parent);
      newBlock.removeChildView(item.id);
      newBlock.replaceWithFakeIds();
      newBlock.aspect = this.selectedAspect;
      newBlock.switchMode(ViewMode.EDIT);
      addToGroupedChildren(newBlock, null);

      // the parent also has a parent already -> just make an alternative block then
      if (item.parent.parent) {
        newBlock.parent = item.parent.parent;
        addVariantToGroupedChildren(item.parent.parent.id, item.parent.id, newBlock.id);

        for (let el of lowerInHierarchy) {
          let view = el.view.findView(item.parent.id);
          view.parent.replaceView(view.id, newBlock);
        }
      }
      // make a new block with the original content, so the parent has the two alternatives (original and new) as child
      // keeping the root the same
      else {
        const baseBlock = buildView({
          id: getFakeId(),
          viewRoot: null,
          aspect: item.parent.aspect,
          type: "block",
          class: ""
        });
        baseBlock.switchMode(ViewMode.EDIT);
        newBlock.classList = "";

        if (item.parent instanceof ViewBlock && baseBlock instanceof ViewBlock) {
          for (let el of lowerInHierarchy) {
            const found = el.view.findView(item.parent.id);
            if (found instanceof ViewBlock) found.children = [newBlock];
          }

          for (let el of this.viewsByAspect.filter(e => lowerInHierarchy.indexOf(e) == -1)) {
            const found = el.view.findView(item.parent.id);
            if (found instanceof ViewBlock) {
              const otherBlock = _.cloneDeep(baseBlock);
              otherBlock.parent = item.parent;
              otherBlock.children = _.cloneDeep(found.children);

              found.children = [otherBlock];

              for (let el of otherBlock.children) {
                el.parent = otherBlock
              }
              otherBlock.parent = item.parent;
            }
          }

          const oldChildren = groupedChildren.get(item.parent.id);
          groupedChildren.set(baseBlock.id, oldChildren);
          groupedChildren.set(item.parent.id, [[baseBlock.id]]);
          addVariantToGroupedChildren(item.parent.id, baseBlock.id, newBlock.id);

          newBlock.parent = item.parent;
        }
      }

    }
    // item has no parent at all -> it's the root we are deleting -> only gets here if it's a new page -> it's fine! just make it null
    else if (!item.parent) {
      this.viewsByAspect = this.viewsByAspect.map(e =>
        _.isEqual(e.aspect, this.selectedAspect) ? { aspect: e.aspect, view: null } : e
      );
      groupedChildren.delete(item.id);
    }
    // no higher -> can just delete it here and in lower aspects
    else {
      for (let el of lowerInHierarchy) {
        let view = el.view.findView(item.id);
        if (view.parent) {
          // visually delete the item from those views
          view.parent.removeChildView(item.id);

          // remove from the grouped children
          let entry = groupedChildren.get(view.parent.id);
          entry.forEach((group, groupIndex) => {
            const itemIndex = group.indexOf(item.id);
            if (itemIndex >= 0) {
              group.splice(itemIndex, 1);
              if (group.length <= 0) {
                entry.splice(groupIndex, 1);
                groupedChildren.set(view.parent.id, entry);
              }
              else {
                groupedChildren.set(view.parent.id, entry);
              }
            }
          })
        }
      }
    }

    // View doesn't exist anymore in any tree -> delete from database
    if (item.id > 0 && this.viewsByAspect.filter((e) => e.view?.findView(item.id)).length <= 0) {
      viewsDeleted.push(item.id);
    }
  }

  /*
   * Duplicates a view
   */
  duplicate(item: View) {
    this.add(item, item.parent, "value")
  }
}

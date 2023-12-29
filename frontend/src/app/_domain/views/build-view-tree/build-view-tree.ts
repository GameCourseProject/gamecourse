import { Aspect } from "../aspects/aspect";
import { View, ViewDatabase } from "../view";

export let viewTree: any[];                           // The view tree being built
export let viewsAdded: Map<number, ViewDatabase>;     // Holds building-blocks that have already been added to the tree
export let viewsDeleted: number[] = [];               // viewIds of views that were completely deleted -> delete from database
export let selectedAspect: Aspect;                    // Selected aspect for previewing and editing
let fakeId: number = -1;                              // Fake, negative ids, for new views, to be generated in backend

export let groupedChildren: Map<number, number[][]>;

export function getFakeId() : number {
  const id = fakeId;
  fakeId -= 1;
  return id;
}

export function setSelectedAspect(aspect: Aspect) {
  selectedAspect = aspect;
}

export function initGroupedChildren(viewTree: any[]) {
  groupedChildren = new Map<number, number[][]>();
  for (let view of viewTree) {
    recursiveGroupChildren(view);
  }
}

function recursiveGroupChildren(view: any) {
  if ('children' in view) {
    for (let child of view.children) {
      const group = groupedChildren.get(view.id) ?? [];
      group.push(child.map((e) => e.id));
      groupedChildren.set(view.id, group);
      for (let el of child) {
        recursiveGroupChildren(el);
      }
    }
  }
}

/**
 * Builds a view tree to be sent to database by merging all aspects
 * according to view ids and viewIds.
 *
 * @param viewsOfAspects
 */
export function buildViewTree(viewsOfAspects: View[]): ViewDatabase[] {
  viewsAdded = new Map<number, ViewDatabase>();
  viewTree = [];

  // Go through each aspect and add to view tree
  for (const view of viewsOfAspects) {
    if (view) view.buildViewTree();
  }
  
  // Clean up unexistent ids
  // This is specially useful if an aspect was deleted, since while building the tree
  // it wont find a matching view for the ids
  recursiveRemoveUnexistent(viewTree[0]);
  function recursiveRemoveUnexistent(view: any) {
    if ('children' in view) {
      for (let group of view.children) {
        for (let child of group) {
          if (typeof child === "number") {
            viewsDeleted.push(child);
          }
          else {
            recursiveRemoveUnexistent(child);
          }
        }
        view.children.splice(view.children.indexOf(group), 1, group.filter(e => typeof e !== "number"));
      }
    }
  }
  return viewTree;
}

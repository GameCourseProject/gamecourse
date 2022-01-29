import {View} from "../view";
import {exists} from "../../../_utils/misc/misc";

export let viewsAdded: Map<number, View>;     // Holds views that have already been added to the tree
export let viewTree: any[];                   // The view tree being built
export let baseFakeId: number;                // The minimum fake ID in the beginning; serves as a base

/**
 * Builds a view tree to be sent to database by merging all aspects
 * according to view ids and viewIds.
 * In cases where a new view should be created in database, by passing
 * the base fake id it will build a view tree with only fake ids.
 *
 * @param aspects
 * @param baseId
 */
export function buildViewTree(aspects: View[], baseId?: number): any[] {
  viewsAdded = new Map<number, View>();
  viewTree = [];
  baseFakeId = exists(baseId) ? baseId : null;

  // Go through each aspect and add to view tree
  for (const aspect of aspects) {
    aspect.buildViewTree();
  }
  return viewTree;
}

import React from 'react';
import {Composition} from 'remotion';
import {EcommercePromo} from './EcommercePromo';
import {FurniSyncImagePromo} from './FurniSyncImagePromo';
import {FurniSyncVerticalPromo} from './FurniSyncVerticalPromo';

export const RemotionRoot: React.FC = () => (
  <>
    <Composition
      id="FurniSyncEcommerce"
      component={EcommercePromo}
      durationInFrames={1800}
      fps={30}
      width={1920}
      height={1080}
    />
    <Composition
      id="FurniSyncImagePromo"
      component={FurniSyncImagePromo}
      durationInFrames={1800}
      fps={30}
      width={1920}
      height={1080}
    />
    <Composition
      id="FurniSyncVerticalPromo"
      component={FurniSyncVerticalPromo}
      durationInFrames={1800}
      fps={30}
      width={1080}
      height={1920}
    />
  </>
);

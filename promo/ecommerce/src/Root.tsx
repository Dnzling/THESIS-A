import React from 'react';
import {Composition} from 'remotion';
import {EcommercePromo} from './EcommercePromo';

export const RemotionRoot: React.FC = () => (
  <Composition
    id="FurniSyncEcommerce"
    component={EcommercePromo}
    durationInFrames={1800}
    fps={30}
    width={1920}
    height={1080}
  />
);
